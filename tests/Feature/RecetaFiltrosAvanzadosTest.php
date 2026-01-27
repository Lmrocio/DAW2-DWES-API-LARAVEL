<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\Like;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecetaFiltrosAvanzadosTest extends TestCase
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
     * Test: Filtrar recetas por ingrediente
     */
    public function test_puede_filtrar_recetas_por_ingrediente(): void
    {
        $user = User::factory()->create();

        // Crear recetas con diferentes ingredientes
        $recetaConHuevo = Receta::factory()->create(['titulo' => 'Tortilla']);
        Ingrediente::factory()->create([
            'receta_id' => $recetaConHuevo->id,
            'nombre' => 'Huevo',
        ]);

        $recetaConPollo = Receta::factory()->create(['titulo' => 'Pollo al horno']);
        Ingrediente::factory()->create([
            'receta_id' => $recetaConPollo->id,
            'nombre' => 'Pollo',
        ]);

        $recetaSinIngredientes = Receta::factory()->create(['titulo' => 'Sin ingredientes']);

        // Filtrar por "huevo"
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=huevo');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('Tortilla', $data[0]['titulo']);
    }

    /**
     * Test: Filtrar por ingrediente (case insensitive)
     */
    public function test_filtro_ingrediente_es_case_insensitive(): void
    {
        $user = User::factory()->create();

        $receta = Receta::factory()->create(['titulo' => 'Tortilla']);
        Ingrediente::factory()->create([
            'receta_id' => $receta->id,
            'nombre' => 'Huevo',
        ]);

        // Buscar con minúsculas
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=huevo');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Buscar con mayúsculas
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=HUEVO');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: Filtrar por ingrediente parcial
     */
    public function test_filtro_ingrediente_busca_parcial(): void
    {
        $user = User::factory()->create();

        $receta = Receta::factory()->create(['titulo' => 'Pasta']);
        Ingrediente::factory()->create([
            'receta_id' => $receta->id,
            'nombre' => 'Aceite de oliva',
        ]);

        // Buscar solo "aceite" debe encontrar "Aceite de oliva"
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=aceite');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: Ordenar por popularidad (más likes primero)
     */
    public function test_puede_ordenar_por_popularidad(): void
    {
        $user = User::factory()->create();

        // Crear recetas con diferentes números de likes
        $recetaPopular = Receta::factory()->create(['titulo' => 'Muy popular']);
        Like::factory()->count(10)->create(['receta_id' => $recetaPopular->id]);

        $recetaMedia = Receta::factory()->create(['titulo' => 'Media']);
        Like::factory()->count(5)->create(['receta_id' => $recetaMedia->id]);

        $recetaPoco = Receta::factory()->create(['titulo' => 'Poco popular']);
        Like::factory()->count(1)->create(['receta_id' => $recetaPoco->id]);

        // Ordenar por popularidad
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=popular');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verificar orden: más popular primero
        $this->assertEquals('Muy popular', $data[0]['titulo']);
        $this->assertEquals(10, $data[0]['likes_count']);

        $this->assertEquals('Media', $data[1]['titulo']);
        $this->assertEquals(5, $data[1]['likes_count']);

        $this->assertEquals('Poco popular', $data[2]['titulo']);
        $this->assertEquals(1, $data[2]['likes_count']);
    }

    /**
     * Test: Ordenar por popularidad descendente con -popular
     */
    public function test_ordenar_por_popularidad_con_prefijo_menos(): void
    {
        $user = User::factory()->create();

        $receta1 = Receta::factory()->create();
        Like::factory()->count(10)->create(['receta_id' => $receta1->id]);

        $receta2 = Receta::factory()->create();
        Like::factory()->count(5)->create(['receta_id' => $receta2->id]);

        // -popular debe ordenar por popularidad (descendente por defecto)
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=-popular');

        $response->assertStatus(200);

        $data = $response->json('data');

        // El primero debe tener más likes
        $this->assertEquals(10, $data[0]['likes_count']);
        $this->assertEquals(5, $data[1]['likes_count']);
    }

    /**
     * Test: Combinar filtro por ingrediente y ordenar por popularidad
     */
    public function test_combinar_filtro_ingrediente_y_ordenar_por_popularidad(): void
    {
        $user = User::factory()->create();

        // Receta 1: con huevo, 10 likes
        $receta1 = Receta::factory()->create(['titulo' => 'Tortilla popular']);
        Ingrediente::factory()->create(['receta_id' => $receta1->id, 'nombre' => 'Huevo']);
        Like::factory()->count(10)->create(['receta_id' => $receta1->id]);

        // Receta 2: con huevo, 5 likes
        $receta2 = Receta::factory()->create(['titulo' => 'Revuelto']);
        Ingrediente::factory()->create(['receta_id' => $receta2->id, 'nombre' => 'Huevo']);
        Like::factory()->count(5)->create(['receta_id' => $receta2->id]);

        // Receta 3: SIN huevo, 20 likes (no debe aparecer)
        $receta3 = Receta::factory()->create(['titulo' => 'Paella']);
        Ingrediente::factory()->create(['receta_id' => $receta3->id, 'nombre' => 'Arroz']);
        Like::factory()->count(20)->create(['receta_id' => $receta3->id]);

        // Filtrar por huevo Y ordenar por popularidad
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=huevo&sort=popular');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Solo deben aparecer las 2 recetas con huevo
        $this->assertCount(2, $data);

        // Ordenadas por popularidad (más likes primero)
        $this->assertEquals('Tortilla popular', $data[0]['titulo']);
        $this->assertEquals(10, $data[0]['likes_count']);

        $this->assertEquals('Revuelto', $data[1]['titulo']);
        $this->assertEquals(5, $data[1]['likes_count']);
    }

    /**
     * Test: Combinar búsqueda de texto, filtro por ingrediente y popularidad
     */
    public function test_combinar_multiples_filtros(): void
    {
        $user = User::factory()->create();

        // Receta 1: título con "española", con huevo, 10 likes
        $receta1 = Receta::factory()->create([
            'titulo' => 'Tortilla española',
            'descripcion' => 'Clásica',
        ]);
        Ingrediente::factory()->create(['receta_id' => $receta1->id, 'nombre' => 'Huevo']);
        Like::factory()->count(10)->create(['receta_id' => $receta1->id]);

        // Receta 2: título sin "española", con huevo, 15 likes (no debe aparecer)
        $receta2 = Receta::factory()->create([
            'titulo' => 'Revuelto de huevos',
            'descripcion' => 'Simple',
        ]);
        Ingrediente::factory()->create(['receta_id' => $receta2->id, 'nombre' => 'Huevo']);
        Like::factory()->count(15)->create(['receta_id' => $receta2->id]);

        // Receta 3: título con "española", SIN huevo, 20 likes (no debe aparecer)
        $receta3 = Receta::factory()->create([
            'titulo' => 'Paella española',
            'descripcion' => 'Tradicional',
        ]);
        Ingrediente::factory()->create(['receta_id' => $receta3->id, 'nombre' => 'Arroz']);
        Like::factory()->count(20)->create(['receta_id' => $receta3->id]);

        // Búsqueda: "española" + ingrediente: "huevo" + ordenar por popularidad
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?q=española&ingrediente=huevo&sort=popular');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Solo debe aparecer la Tortilla española
        $this->assertCount(1, $data);
        $this->assertEquals('Tortilla española', $data[0]['titulo']);
    }

    /**
     * Test: Ordenar por título sigue funcionando
     */
    public function test_ordenar_por_titulo_sigue_funcionando(): void
    {
        $user = User::factory()->create();

        Receta::factory()->create(['titulo' => 'Zebra']);
        Receta::factory()->create(['titulo' => 'Arroz']);
        Receta::factory()->create(['titulo' => 'Manzana']);

        // Ordenar por título ascendente
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=titulo');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertEquals('Arroz', $data[0]['titulo']);
        $this->assertEquals('Manzana', $data[1]['titulo']);
        $this->assertEquals('Zebra', $data[2]['titulo']);
    }

    /**
     * Test: Ordenar por título descendente
     */
    public function test_ordenar_por_titulo_descendente(): void
    {
        $user = User::factory()->create();

        Receta::factory()->create(['titulo' => 'Arroz']);
        Receta::factory()->create(['titulo' => 'Zebra']);
        Receta::factory()->create(['titulo' => 'Manzana']);

        // Ordenar por título descendente
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=-titulo');

        $response->assertStatus(200);

        $data = $response->json('data');

        $this->assertEquals('Zebra', $data[0]['titulo']);
        $this->assertEquals('Manzana', $data[1]['titulo']);
        $this->assertEquals('Arroz', $data[2]['titulo']);
    }

    /**
     * Test: Filtro sin parámetros devuelve todas las recetas
     */
    public function test_sin_filtros_devuelve_todas_las_recetas(): void
    {
        $user = User::factory()->create();

        Receta::factory()->count(5)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/recetas');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test: Scope conIngrediente sin parámetro no afecta la consulta
     */
    public function test_scope_con_ingrediente_null_no_afecta_consulta(): void
    {
        $user = User::factory()->create();

        Receta::factory()->count(3)->create();

        // Sin parámetro ingrediente
        $response = $this->actingAs($user)
            ->getJson('/api/recetas');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test: Recetas sin likes aparecen al ordenar por popularidad
     */
    public function test_recetas_sin_likes_aparecen_al_final_por_popularidad(): void
    {
        $user = User::factory()->create();

        $recetaConLikes = Receta::factory()->create(['titulo' => 'Popular']);
        Like::factory()->count(5)->create(['receta_id' => $recetaConLikes->id]);

        $recetaSinLikes = Receta::factory()->create(['titulo' => 'Sin likes']);

        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=popular');

        $response->assertStatus(200);

        $data = $response->json('data');

        // La popular debe aparecer primero
        $this->assertEquals('Popular', $data[0]['titulo']);
        $this->assertEquals(5, $data[0]['likes_count']);

        // La sin likes debe aparecer después
        $this->assertEquals('Sin likes', $data[1]['titulo']);
        $this->assertEquals(0, $data[1]['likes_count']);
    }
}
