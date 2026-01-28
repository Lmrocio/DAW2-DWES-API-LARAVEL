<?php

namespace Tests\Feature;

use App\Models\Comentario;
use App\Models\Like;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de verificación de extensiones obligatorias y opcionales
 *
 * Verifica:
 * - Autorización en comentarios
 * - Sistema de likes con toggle
 * - Validación de subida de imágenes
 * - Integridad de datos
 */
class ExtensionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Crear roles necesarios
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'user']);

        // Fake storage para tests de imágenes
        Storage::fake('public');
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Comentarios - Autorización
    |--------------------------------------------------------------------------
    */

    /**
     * Test: Un usuario NO puede borrar un comentario de otro usuario (403)
     *
     * Requisito crítico: Solo el autor o admin pueden eliminar comentarios
     */
    public function test_usuario_no_puede_borrar_comentario_de_otro_usuario(): void
    {
        $autor = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create();

        // El autor crea un comentario
        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
            'texto' => 'Comentario del autor',
        ]);

        // Otro usuario intenta eliminarlo
        $response = $this->actingAs($otroUsuario)
            ->deleteJson("/api/comentarios/{$comentario->id}");

        // Debe ser 403 Forbidden
        $response->assertStatus(403);

        // El comentario debe seguir existiendo
        $this->assertDatabaseHas('comentarios', [
            'id' => $comentario->id,
            'texto' => 'Comentario del autor',
        ]);
    }

    /**
     * Test: El autor SÍ puede borrar su propio comentario
     */
    public function test_autor_puede_borrar_su_propio_comentario(): void
    {
        $autor = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
        ]);

        $response = $this->actingAs($autor)
            ->deleteJson("/api/comentarios/{$comentario->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('comentarios', [
            'id' => $comentario->id,
        ]);
    }

    /**
     * Test: Un admin SÍ puede borrar comentarios de otros
     */
    public function test_admin_puede_borrar_comentario_de_otro_usuario(): void
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
     * Test: Un usuario NO puede editar un comentario de otro
     */
    public function test_usuario_no_puede_editar_comentario_de_otro(): void
    {
        $autor = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create();

        $comentario = Comentario::factory()->create([
            'user_id' => $autor->id,
            'receta_id' => $receta->id,
            'texto' => 'Texto original',
        ]);

        $response = $this->actingAs($otroUsuario)
            ->putJson("/api/comentarios/{$comentario->id}", [
                'texto' => 'Intento de modificación',
            ]);

        $response->assertStatus(403);

        // El texto no debe haber cambiado
        $this->assertDatabaseHas('comentarios', [
            'id' => $comentario->id,
            'texto' => 'Texto original',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Likes - Funcionalidad Toggle y Contador
    |--------------------------------------------------------------------------
    */

    /**
     * Test: Un usuario puede dar like y el contador sube
     *
     * Requisito crítico: El sistema de likes debe funcionar correctamente
     */
    public function test_usuario_puede_dar_like_y_contador_sube(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Verificar estado inicial
        $this->assertEquals(0, $receta->likes()->count());

        // Dar like
        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(201)
            ->assertJson([
                'liked' => true,
                'likes_count' => 1,
            ]);

        // Verificar en base de datos
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Verificar contador
        $this->assertEquals(1, $receta->fresh()->likes()->count());
    }

    /**
     * Test: El contador de likes se actualiza correctamente
     */
    public function test_contador_de_likes_se_actualiza_correctamente(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $receta = Receta::factory()->create();

        // Usuario 1 da like
        $this->actingAs($user1)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertJson(['likes_count' => 1]);

        // Usuario 2 da like
        $this->actingAs($user2)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertJson(['likes_count' => 2]);

        // Usuario 3 da like
        $this->actingAs($user3)
            ->postJson("/api/recetas/{$receta->id}/like")
            ->assertJson(['likes_count' => 3]);

        // Verificar contador final
        $this->assertEquals(3, $receta->fresh()->likes()->count());
    }

    /**
     * Test: Toggle de like - dar y quitar correctamente
     */
    public function test_toggle_like_funciona_correctamente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Primera llamada: dar like
        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(201)
            ->assertJson([
                'liked' => true,
                'likes_count' => 1,
            ]);

        // Segunda llamada: quitar like (toggle)
        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'liked' => false,
                'likes_count' => 0,
            ]);

        // Verificar que no hay likes en BD
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);
    }

    /**
     * Test: La restricción UNIQUE evita likes duplicados
     */
    public function test_restriccion_unique_evita_likes_duplicados(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear un like manualmente
        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);

        // Intentar crear otro like directamente (debe fallar por constraint)
        $this->expectException(\Illuminate\Database\QueryException::class);

        Like::create([
            'user_id' => $user->id,
            'receta_id' => $receta->id,
        ]);
    }

    /**
     * Test: RecetaResource incluye el contador de likes
     */
    public function test_receta_resource_incluye_contador_de_likes(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create();

        // Crear 5 likes
        Like::factory()->count(5)->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 5]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Imágenes - Validación
    |--------------------------------------------------------------------------
    */

    /**
     * Test: No se pueden subir archivos que no sean imágenes
     *
     * Requisito crítico: Solo se aceptan imágenes (jpeg, png, jpg)
     */
    public function test_no_se_pueden_subir_archivos_que_no_sean_imagenes(): void
    {
        $user = User::factory()->create();

        // Intentar subir un PDF
        $archivoPDF = UploadedFile::fake()->create('documento.pdf', 500);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta de prueba',
                'descripcion' => 'Descripción',
                'instrucciones' => 'Instrucciones',
                'imagen' => $archivoPDF,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /**
     * Test: Intentar subir un archivo de texto falla
     */
    public function test_intentar_subir_archivo_texto_falla(): void
    {
        $user = User::factory()->create();

        $archivoTxt = UploadedFile::fake()->create('archivo.txt', 100);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $archivoTxt,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /**
     * Test: Intentar subir un archivo Word falla
     */
    public function test_intentar_subir_archivo_word_falla(): void
    {
        $user = User::factory()->create();

        $archivoDoc = UploadedFile::fake()->create('documento.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $archivoDoc,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /**
     * Test: Solo se aceptan formatos válidos (jpeg, png, jpg)
     */
    public function test_solo_se_aceptan_formatos_validos(): void
    {
        $user = User::factory()->create();

        // JPEG válido
        $imagenJPEG = UploadedFile::fake()->image('plato.jpeg')->size(1024);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta JPEG',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $imagenJPEG,
            ]);

        $response->assertStatus(201);
        Storage::disk('public')->assertExists(Receta::latest()->first()->imagen_url);

        // PNG válido
        $imagenPNG = UploadedFile::fake()->image('plato.png')->size(1024);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta PNG',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $imagenPNG,
            ]);

        $response->assertStatus(201);

        // JPG válido
        $imagenJPG = UploadedFile::fake()->image('plato.jpg')->size(1024);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta JPG',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $imagenJPG,
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test: Validación de tamaño máximo 2MB
     */
    public function test_validacion_tamano_maximo_2mb(): void
    {
        $user = User::factory()->create();

        // Imagen de 3MB (excede el límite)
        $imagenGrande = UploadedFile::fake()->image('plato.jpg')->size(3072); // 3MB

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Receta',
                'descripcion' => 'Desc',
                'instrucciones' => 'Inst',
                'imagen' => $imagenGrande,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['imagen']);
    }

    /*
    |--------------------------------------------------------------------------
    | Tests de Integridad y Regresión
    |--------------------------------------------------------------------------
    */

    /**
     * Test: Crear receta completa con todos los componentes
     */
    public function test_crear_receta_completa_con_todos_los_componentes(): void
    {
        $user = User::factory()->create();

        // 1. Crear receta con imagen
        $imagen = UploadedFile::fake()->image('paella.jpg')->size(1024);

        $response = $this->actingAs($user)
            ->postJson('/api/recetas', [
                'titulo' => 'Paella Valenciana',
                'descripcion' => 'Auténtica paella',
                'instrucciones' => 'Paso 1, Paso 2, Paso 3',
                'imagen' => $imagen,
            ]);

        $response->assertStatus(201);
        $recetaId = $response->json('id');

        // 2. Agregar ingredientes
        $this->actingAs($user)
            ->postJson("/api/recetas/{$recetaId}/ingredientes", [
                'nombre' => 'Arroz',
                'cantidad' => '400',
                'unidad' => 'g',
            ])
            ->assertStatus(201);

        // 3. Dar like
        $this->actingAs($user)
            ->postJson("/api/recetas/{$recetaId}/like")
            ->assertStatus(201);

        // 4. Comentar
        $this->actingAs($user)
            ->postJson("/api/recetas/{$recetaId}/comentarios", [
                'texto' => '¡Excelente receta!',
            ])
            ->assertStatus(201);

        // 5. Ver receta completa
        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$recetaId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'titulo',
                    'imagen_url',
                    'ingredientes',
                    'likes_count',
                    'liked_by_user',
                    'comentarios',
                    'comentarios_count',
                ]
            ])
            ->assertJson([
                'data' => [
                    'likes_count' => 1,
                    'comentarios_count' => 1,
                ]
            ]);
    }

    /**
     * Test: Filtros avanzados funcionan correctamente
     */
    public function test_filtros_avanzados_funcionan_correctamente(): void
    {
        $user = User::factory()->create();

        // Crear recetas de prueba
        $receta1 = Receta::factory()->create(['titulo' => 'Paella']);
        $receta1->ingredientes()->create(['nombre' => 'Arroz', 'cantidad' => '400', 'unidad' => 'g']);
        Like::factory()->count(10)->create(['receta_id' => $receta1->id]);

        $receta2 = Receta::factory()->create(['titulo' => 'Tortilla']);
        $receta2->ingredientes()->create(['nombre' => 'Huevo', 'cantidad' => '4', 'unidad' => 'ud']);
        Like::factory()->count(5)->create(['receta_id' => $receta2->id]);

        // Test: Filtrar por ingrediente
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?ingrediente=arroz');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // Test: Ordenar por popularidad
        $response = $this->actingAs($user)
            ->getJson('/api/recetas?sort=popular');

        $response->assertStatus(200);
        $data = $response->json('data');

        // La primera debe ser la más popular
        $this->assertEquals($receta1->id, $data[0]['id']);
        $this->assertEquals(10, $data[0]['likes_count']);
    }

    /**
     * Test: No hay regresiones en endpoints existentes
     */
    public function test_no_hay_regresiones_en_endpoints_existentes(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // GET /api/recetas
        $response = $this->actingAs($user)->getJson('/api/recetas');
        $response->assertStatus(200);

        // GET /api/recetas/{id}
        $response = $this->actingAs($user)->getJson("/api/recetas/{$receta->id}");
        $response->assertStatus(200);

        // PUT /api/recetas/{id}
        $response = $this->actingAs($user)->putJson("/api/recetas/{$receta->id}", [
            'titulo' => 'Título actualizado',
        ]);
        $response->assertStatus(200);

        // DELETE /api/recetas/{id}
        $response = $this->actingAs($user)->deleteJson("/api/recetas/{$receta->id}");
        $response->assertStatus(200);
    }

    /**
     * Test: Autenticación requerida en todos los endpoints
     */
    public function test_autenticacion_requerida_en_todos_los_endpoints(): void
    {
        $receta = Receta::factory()->create();

        // Sin autenticación debe dar 401
        $this->getJson('/api/recetas')->assertStatus(401);
        $this->postJson('/api/recetas', [])->assertStatus(401);
        $this->getJson("/api/recetas/{$receta->id}")->assertStatus(401);
        $this->putJson("/api/recetas/{$receta->id}", [])->assertStatus(401);
        $this->deleteJson("/api/recetas/{$receta->id}")->assertStatus(401);
        $this->postJson("/api/recetas/{$receta->id}/like")->assertStatus(401);
        $this->postJson("/api/recetas/{$receta->id}/comentarios", [])->assertStatus(401);
    }

    /**
     * Test: Cascade delete funciona correctamente
     */
    public function test_cascade_delete_funciona_correctamente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // Crear datos relacionados
        $receta->ingredientes()->create(['nombre' => 'Test', 'cantidad' => '1', 'unidad' => 'ud']);
        Like::create(['user_id' => $user->id, 'receta_id' => $receta->id]);
        Comentario::create(['user_id' => $user->id, 'receta_id' => $receta->id, 'texto' => 'Test']);

        $recetaId = $receta->id;

        // Eliminar receta
        $receta->delete();

        // Verificar que los datos relacionados fueron eliminados
        $this->assertEquals(0, \DB::table('ingredientes')->where('receta_id', $recetaId)->count());
        $this->assertEquals(0, \DB::table('likes')->where('receta_id', $recetaId)->count());
        $this->assertEquals(0, \DB::table('comentarios')->where('receta_id', $recetaId)->count());
    }
}
