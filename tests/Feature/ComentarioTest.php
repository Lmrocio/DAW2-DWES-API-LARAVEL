<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComentarioTest extends TestCase
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
     * Test: Cualquier usuario autenticado puede comentar una receta
     */
    public function test_usuario_autenticado_puede_comentar_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $data = [
            'texto' => 'Excelente receta, me encantó!',
        ];

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/comentarios", $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'texto' => 'Excelente receta, me encantó!',
                'user_name' => $user->name,
            ]);

        $this->assertDatabaseHas('comentarios', [
            'user_id' => $user->id,
            'receta_id' => $receta->id,
            'texto' => 'Excelente receta, me encantó!',
        ]);
    }

    /**
     * Test: Usuario no autenticado no puede comentar
     */
    public function test_usuario_no_autenticado_no_puede_comentar(): void
    {
        $receta = Receta::factory()->create();

        $response = $this->postJson("/api/recetas/{$receta->id}/comentarios", [
            'texto' => 'Intento de comentario sin autenticación',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Validación del campo texto requerido
     */
    public function test_validacion_texto_requerido(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/comentarios", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['texto']);
    }

    /**
     * Test: Validación del campo texto máximo 1000 caracteres
     */
    public function test_validacion_texto_maximo_1000_caracteres(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $textoLargo = str_repeat('a', 1001);

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/comentarios", [
                'texto' => $textoLargo,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['texto']);
    }

    /**
     * Test: Listar comentarios de una receta
     */
    public function test_puede_listar_comentarios_de_una_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear 3 comentarios
        Comentario::factory()->count(3)->create([
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}/comentarios");

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'receta_id',
                    'user_id',
                    'user_name',
                    'texto',
                    'created_at',
                ]
            ]);
    }

    /**
     * Test: El autor puede eliminar su comentario
     */
    public function test_autor_puede_eliminar_su_comentario(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comentarios/{$comentario->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comentario eliminado correctamente']);

        $this->assertDatabaseMissing('comentarios', [
            'id' => $comentario->id,
        ]);
    }

    /**
     * Test: Un usuario NO puede eliminar comentarios de otros
     */
    public function test_usuario_no_puede_eliminar_comentario_ajeno(): void
    {
        $autor = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($otroUsuario)
            ->deleteJson("/api/comentarios/{$comentario->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('comentarios', [
            'id' => $comentario->id,
        ]);
    }

    /**
     * Test: Admin puede eliminar cualquier comentario
     */
    public function test_admin_puede_eliminar_cualquier_comentario(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $autor = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/comentarios/{$comentario->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comentarios', [
            'id' => $comentario->id,
        ]);
    }

    /**
     * Test: El autor puede actualizar su comentario
     */
    public function test_autor_puede_actualizar_su_comentario(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
            'texto' => 'Comentario original',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/comentarios/{$comentario->id}", [
                'texto' => 'Comentario actualizado',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'texto' => 'Comentario actualizado',
            ]);

        $this->assertDatabaseHas('comentarios', [
            'id' => $comentario->id,
            'texto' => 'Comentario actualizado',
        ]);
    }

    /**
     * Test: Un usuario NO puede actualizar comentarios de otros
     */
    public function test_usuario_no_puede_actualizar_comentario_ajeno(): void
    {
        $autor = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($otroUsuario)
            ->putJson("/api/comentarios/{$comentario->id}", [
                'texto' => 'Intento de actualización',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Ver receta incluye comentarios
     */
    public function test_ver_receta_incluye_comentarios(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear comentarios
        Comentario::factory()->count(2)->create([
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'titulo',
                'comentarios' => [
                    '*' => [
                        'id',
                        'texto',
                        'user_name',
                    ]
                ],
                'comentarios_count',
            ])
            ->assertJsonFragment(['comentarios_count' => 2]);
    }

    /**
     * Test: Al eliminar una receta se eliminan sus comentarios (cascade)
     */
    public function test_al_eliminar_receta_se_eliminan_comentarios(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // Crear comentarios
        Comentario::factory()->count(3)->create(['receta_id' => $receta->id]);

        $comentariosCount = Comentario::where('receta_id', $receta->id)->count();
        $this->assertEquals(3, $comentariosCount);

        // Eliminar receta
        $receta->delete();

        // Verificar que los comentarios fueron eliminados
        $comentariosCount = Comentario::where('receta_id', $receta->id)->count();
        $this->assertEquals(0, $comentariosCount);
    }

    /**
     * Test: Los comentarios se ordenan por más reciente primero
     */
    public function test_comentarios_se_ordenan_por_mas_reciente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear comentarios en orden
        $comentario1 = Comentario::factory()->create([
            'receta_id' => $receta->id,
            'texto' => 'Primero',
            'created_at' => now()->subHours(2),
        ]);

        $comentario2 = Comentario::factory()->create([
            'receta_id' => $receta->id,
            'texto' => 'Segundo',
            'created_at' => now()->subHours(1),
        ]);

        $comentario3 = Comentario::factory()->create([
            'receta_id' => $receta->id,
            'texto' => 'Tercero (más reciente)',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}/comentarios");

        $response->assertStatus(200);

        $comentarios = $response->json();

        // El primero debe ser el más reciente
        $this->assertEquals('Tercero (más reciente)', $comentarios[0]['texto']);
        $this->assertEquals('Segundo', $comentarios[1]['texto']);
        $this->assertEquals('Primero', $comentarios[2]['texto']);
    }
}
