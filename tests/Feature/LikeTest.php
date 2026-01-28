<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear roles necesarios
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'user']);
    }

    /**
     * Test: Un usuario puede dar like a una receta
     */
    public function test_usuario_puede_dar_like_a_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Like añadido correctamente',
                'liked' => true,
                'likes_count' => 1,
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);
    }

    /**
     * Test: Un usuario puede quitar su like de una receta (toggle)
     */
    public function test_usuario_puede_quitar_like_de_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Primero dar like
        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Luego quitarlo (toggle)
        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Like eliminado correctamente',
                'liked' => false,
                'likes_count' => 0,
            ]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);
    }

    /**
     * Test: Un usuario no puede dar más de un like a la misma receta
     */
    public function test_usuario_no_puede_dar_mas_de_un_like_a_misma_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear el primer like
        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Intentar crear un segundo like directamente en la BD debe fallar por constraint
        $this->expectException(\Illuminate\Database\QueryException::class);

        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);
    }

    /**
     * Test: Varios usuarios pueden dar like a la misma receta
     */
    public function test_varios_usuarios_pueden_dar_like_a_misma_receta(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $receta = Receta::factory()->create();

        // User 1 da like
        $this->actingAs($user1)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertStatus(201);

        // User 2 da like
        $this->actingAs($user2)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertStatus(201);

        // User 3 da like
        $this->actingAs($user3)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertStatus(201);

        // Verificar que hay 3 likes
        $this->assertEquals(3, $receta->fresh()->likes()->count());
    }

    /**
     * Test: Obtener el contador de likes de una receta
     */
    public function test_puede_obtener_contador_de_likes(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear 5 likes
        Like::factory()->count(5)->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}/likes/count");

        $response->assertStatus(200)
            ->assertJson([
                'likes_count' => 5,
            ]);
    }

    /**
     * Test: Listar usuarios que han dado like a una receta
     */
    public function test_puede_listar_usuarios_que_dieron_like(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear 3 likes
        $users = User::factory()->count(3)->create();
        foreach ($users as $u) {
            Like::create([
                'user_id' => $u->id,
                'receta_id' => $receta->id,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}/likes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'likes' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'created_at',
                    ]
                ],
                'likes_count',
            ])
            ->assertJson([
                'likes_count' => 3,
            ]);
    }

    /**
     * Test: RecetaResource incluye likes_count
     */
    public function test_receta_resource_incluye_likes_count(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear 2 likes
        Like::factory()->count(2)->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'titulo',
                    'likes_count',
                    'liked_by_user',
                ]
            ]);
    }

    /**
     * Test: RecetaResource muestra si el usuario autenticado ha dado like
     */
    public function test_receta_resource_muestra_si_usuario_dio_like(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Sin like
        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJson([ 'data' => ['liked_by_user' => false] ]);

        // Dar like
        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Con like
        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJson([ 'data' => ['liked_by_user' => true] ]);
    }

    /**
     * Test: Al eliminar una receta se eliminan sus likes (cascade)
     */
    public function test_al_eliminar_receta_se_eliminan_likes(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // Crear likes
        Like::factory()->count(3)->create(['receta_id' => $receta->id]);

        $likesCount = Like::where('receta_id', $receta->id)->count();
        $this->assertEquals(3, $likesCount);

        // Eliminar receta
        $receta->delete();

        // Verificar que los likes fueron eliminados
        $likesCount = Like::where('receta_id', $receta->id)->count();
        $this->assertEquals(0, $likesCount);
    }

    /**
     * Test: El listado de recetas incluye likes_count
     */
    public function test_listado_de_recetas_incluye_likes_count(): void
    {
        $user = User::factory()->create();

        // Crear recetas con diferentes likes
        $receta1 = Receta::factory()->create();
        $receta2 = Receta::factory()->create();

        Like::factory()->count(5)->create(['receta_id' => $receta1->id]);
        Like::factory()->count(2)->create(['receta_id' => $receta2->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'titulo',
                        'likes_count',
                    ]
                ]
            ]);
    }

    /**
     * Test: Un usuario no autenticado no puede dar like
     */
    public function test_usuario_no_autenticado_no_puede_dar_like(): void
    {
        $receta = Receta::factory()->create();

        $response = $this->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(401);
    }
}
