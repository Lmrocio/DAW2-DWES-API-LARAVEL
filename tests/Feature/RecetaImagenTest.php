<?php

namespace Tests\Feature;

use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecetaImagenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear roles necesarios
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'user']);

        // Fake del storage para testing
        Storage::fake('public');
    }

    /**
     * Test: Crear receta con imagen
     */
    public function test_puede_crear_receta_con_imagen(): void
    {
        $user = User::factory()->create();

        $imagen = UploadedFile::fake()->image('plato.jpg', 800, 600)->size(1024); // 1MB

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Paella Valenciana',
                'descripcion' => 'Deliciosa paella',
                'instrucciones' => 'Paso 1, Paso 2...',
                'imagen' => $imagen,
            ]);

        $response->assertStatus(201);

        // Verificar que la imagen se guardó
        $receta = Receta::first();
        $this->assertNotNull($receta->imagen_url);
        Storage::disk('public')->assertExists($receta->imagen_url);
    }

    /**
     * Test: Crear receta sin imagen (opcional)
     */
    public function test_puede_crear_receta_sin_imagen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Tortilla',
                'descripcion' => 'Simple tortilla',
                'instrucciones' => 'Batir huevos...',
            ]);

        $response->assertStatus(201);

        $receta = Receta::first();
        $this->assertNull($receta->imagen_url);
    }

    /**
     * Test: Validación - solo acepta imágenes
     */
    public function test_validacion_solo_acepta_imagenes(): void
    {
        $user = User::factory()->create();

        $archivo = UploadedFile::fake()->create('documento.pdf', 500);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta',
                'descripcion' => 'Descripción',
                'instrucciones' => 'Instrucciones',
                'imagen' => $archivo,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /**
     * Test: Validación - tamaño máximo 2MB
     */
    public function test_validacion_tamano_maximo_2mb(): void
    {
        $user = User::factory()->create();

        // Crear imagen de 3MB (excede el límite)
        $imagenGrande = UploadedFile::fake()->image('plato.jpg')->size(3072); // 3MB

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta',
                'descripcion' => 'Descripción',
                'instrucciones' => 'Instrucciones',
                'imagen' => $imagenGrande,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /**
     * Test: Validación - formatos válidos (jpeg, png, jpg)
     */
    public function test_validacion_formatos_validos(): void
    {
        $user = User::factory()->create();

        // PNG válido
        $imagenPng = UploadedFile::fake()->image('plato.png')->size(1024);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta PNG',
                'descripcion' => 'Con imagen PNG',
                'instrucciones' => 'Instrucciones',
                'imagen' => $imagenPng,
            ]);

        $response->assertStatus(201);
        Storage::disk('public')->assertExists(Receta::first()->imagen_url);
    }

    /**
     * Test: Actualizar receta agregando imagen
     */
    public function test_puede_actualizar_receta_agregando_imagen(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => null,
        ]);

        $imagen = UploadedFile::fake()->image('plato_nuevo.jpg')->size(1024);

        $response = $this->actingAs($user)
            ->putJson("/api/recetas/{$receta->id}", [
                'titulo' => $receta->titulo,
                'descripcion' => $receta->descripcion,
                'instrucciones' => $receta->instrucciones,
                'imagen' => $imagen,
            ]);

        $response->assertStatus(200);

        $receta->refresh();
        $this->assertNotNull($receta->imagen_url);
        Storage::disk('public')->assertExists($receta->imagen_url);
    }

    /**
     * Test: Actualizar receta reemplazando imagen anterior
     */
    public function test_puede_actualizar_receta_reemplazando_imagen(): void
    {
        $user = User::factory()->create();

        // Crear imagen inicial
        $imagenInicial = UploadedFile::fake()->image('plato_viejo.jpg')->size(1024);
        $pathInicial = $imagenInicial->store('recetas', 'public');

        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => $pathInicial,
        ]);

        // Verificar que la imagen inicial existe
        Storage::disk('public')->assertExists($pathInicial);

        // Actualizar con nueva imagen
        $imagenNueva = UploadedFile::fake()->image('plato_nuevo.jpg')->size(1024);

        $response = $this->actingAs($user)
            ->putJson("/api/recetas/{$receta->id}", [
                'titulo' => $receta->titulo,
                'descripcion' => $receta->descripcion,
                'instrucciones' => $receta->instrucciones,
                'imagen' => $imagenNueva,
            ]);

        $response->assertStatus(200);

        $receta->refresh();

        // La imagen vieja debe haber sido eliminada
        Storage::disk('public')->assertMissing($pathInicial);

        // La nueva imagen debe existir
        Storage::disk('public')->assertExists($receta->imagen_url);
    }

    /**
     * Test: Eliminar receta elimina también la imagen
     */
    public function test_eliminar_receta_elimina_imagen(): void
    {
        $user = User::factory()->create();

        // Crear imagen
        $imagen = UploadedFile::fake()->image('plato.jpg')->size(1024);
        $path = $imagen->store('recetas', 'public');

        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => $path,
        ]);

        // Verificar que la imagen existe
        Storage::disk('public')->assertExists($path);

        // Eliminar receta
        $response = $this->actingAs($user)
            ->deleteJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200);

        // Verificar que la imagen fue eliminada
        Storage::disk('public')->assertMissing($path);
    }

    /**
     * Test: RecetaResource incluye imagen_url
     */
    public function test_receta_resource_incluye_imagen_url(): void
    {
        $user = User::factory()->create();

        $imagen = UploadedFile::fake()->image('plato.jpg')->size(1024);
        $path = $imagen->store('recetas', 'public');

        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => $path,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'titulo',
                    'imagen_url',
                ]
            ]);

        // Verificar que la URL es completa (contiene http)
        $data = $response->json('data');
        $this->assertNotNull($data['imagen_url']);
        $this->assertStringContainsString('storage/recetas/', $data['imagen_url']);
    }

    /**
     * Test: Receta sin imagen devuelve null en imagen_url
     */
    public function test_receta_sin_imagen_devuelve_null(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'imagen_url' => null,
                ]
            ]);
    }

    /**
     * Test: Actualizar solo texto sin tocar la imagen
     */
    public function test_actualizar_texto_sin_modificar_imagen(): void
    {
        $user = User::factory()->create();

        $imagen = UploadedFile::fake()->image('plato.jpg')->size(1024);
        $path = $imagen->store('recetas', 'public');

        $receta = Receta::factory()->create([
            'user_id' => $user->id,
            'imagen_url' => $path,
        ]);

        $pathOriginal = $receta->imagen_url;

        // Actualizar solo el título
        $response = $this->actingAs($user)
            ->putJson("/api/recetas/{$receta->id}", [
                'titulo' => 'Nuevo Título',
            ]);

        $response->assertStatus(200);

        $receta->refresh();

        // La imagen debe seguir siendo la misma
        $this->assertEquals($pathOriginal, $receta->imagen_url);
        Storage::disk('public')->assertExists($pathOriginal);
    }
}
