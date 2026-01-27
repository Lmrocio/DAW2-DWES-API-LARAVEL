<?php

namespace Tests\Feature;

use App\Models\Ingrediente;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredienteTest extends TestCase
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
     * Test: Listar ingredientes de una receta
     */
    public function test_puede_listar_ingredientes_de_una_receta(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // Crear 3 ingredientes para esta receta
        Ingrediente::factory()->count(3)->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}/ingredientes");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test: El propietario puede agregar ingredientes a su receta
     */
    public function test_propietario_puede_agregar_ingrediente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        $data = [
            'nombre' => 'Huevo',
            'cantidad' => '3',
            'unidad' => 'ud',
        ];

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/ingredientes", $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'nombre' => 'Huevo',
                'cantidad' => '3',
                'unidad' => 'ud',
            ]);

        $this->assertDatabaseHas('ingredientes', [
            'receta_id' => $receta->id,
            'nombre' => 'Huevo',
        ]);
    }

    /**
     * Test: Un usuario NO puede agregar ingredientes a recetas de otros
     */
    public function test_usuario_no_puede_agregar_ingrediente_a_receta_ajena(): void
    {
        $propietario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $propietario->id]);

        $data = [
            'nombre' => 'Sal',
            'cantidad' => '1',
            'unidad' => 'pizca',
        ];

        $response = $this->actingAs($otroUsuario)
            ->postJson("/api/recetas/{$receta->id}/ingredientes", $data);

        $response->assertStatus(403);
    }

    /**
     * Test: Admin puede agregar ingredientes a cualquier receta
     */
    public function test_admin_puede_agregar_ingrediente_a_cualquier_receta(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $propietario = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $propietario->id]);

        $data = [
            'nombre' => 'Pimienta',
            'cantidad' => '1',
            'unidad' => 'pizca',
        ];

        $response = $this->actingAs($admin)
            ->postJson("/api/recetas/{$receta->id}/ingredientes", $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['nombre' => 'Pimienta']);

        $this->assertDatabaseHas('ingredientes', [
            'receta_id' => $receta->id,
            'nombre' => 'Pimienta',
        ]);
    }

    /**
     * Test: Validación de campos requeridos
     */
    public function test_validacion_campos_requeridos_al_crear_ingrediente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/recetas/{$receta->id}/ingredientes", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'cantidad', 'unidad']);
    }

    /**
     * Test: El propietario puede actualizar ingredientes de su receta
     */
    public function test_propietario_puede_actualizar_ingrediente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);
        $ingrediente = Ingrediente::factory()->create([
            'receta_id' => $receta->id,
            'nombre' => 'Huevo',
            'cantidad' => '2',
            'unidad' => 'ud',
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/ingredientes/{$ingrediente->id}", [
                'cantidad' => '4',
                'unidad' => 'ud',
                'nombre' => 'Huevo',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['cantidad' => '4']);

        $this->assertDatabaseHas('ingredientes', [
            'id' => $ingrediente->id,
            'cantidad' => '4',
        ]);
    }

    /**
     * Test: Un usuario NO puede actualizar ingredientes de recetas ajenas
     */
    public function test_usuario_no_puede_actualizar_ingrediente_de_receta_ajena(): void
    {
        $propietario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $propietario->id]);
        $ingrediente = Ingrediente::factory()->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($otroUsuario)
            ->putJson("/api/ingredientes/{$ingrediente->id}", [
                'cantidad' => '10',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: El propietario puede eliminar ingredientes de su receta
     */
    public function test_propietario_puede_eliminar_ingrediente(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);
        $ingrediente = Ingrediente::factory()->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/ingredientes/{$ingrediente->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Ingrediente eliminado correctamente']);

        $this->assertDatabaseMissing('ingredientes', [
            'id' => $ingrediente->id,
        ]);
    }

    /**
     * Test: Un usuario NO puede eliminar ingredientes de recetas ajenas
     */
    public function test_usuario_no_puede_eliminar_ingrediente_de_receta_ajena(): void
    {
        $propietario = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $propietario->id]);
        $ingrediente = Ingrediente::factory()->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($otroUsuario)
            ->deleteJson("/api/ingredientes/{$ingrediente->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Admin puede eliminar ingredientes de cualquier receta
     */
    public function test_admin_puede_eliminar_ingrediente_de_cualquier_receta(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $propietario = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $propietario->id]);
        $ingrediente = Ingrediente::factory()->create(['receta_id' => $receta->id]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/ingredientes/{$ingrediente->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ingredientes', [
            'id' => $ingrediente->id,
        ]);
    }

    /**
     * Test: Ver receta incluye ingredientes
     */
    public function test_ver_receta_incluye_ingredientes(): void
    {
        $user = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $user->id]);

        // Crear ingredientes
        Ingrediente::factory()->create([
            'receta_id' => $receta->id,
            'nombre' => 'Harina',
            'cantidad' => '200',
            'unidad' => 'g',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/recetas/{$receta->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'titulo',
                'ingredientes' => [
                    '*' => [
                        'id',
                        'nombre',
                        'cantidad',
                        'unidad',
                    ]
                ]
            ])
            ->assertJsonFragment(['nombre' => 'Harina']);
    }
}
