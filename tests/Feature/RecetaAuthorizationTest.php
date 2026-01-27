<?php

namespace Tests\Feature;

use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class RecetaAuthorizationTest extends TestCase
{
    use RefreshDatabase;
    // Guía docente: ver docs/06_tests.md.

    public function test_owner_can_update_receta(): void
    {
        $owner = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $owner->id]);

        $token = $owner->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/recetas/{$receta->id}", ['titulo' => 'Nuevo título'])
            ->assertStatus(200)
            ->assertJsonFragment(['titulo' => 'Nuevo título']);
    }

    public function test_non_owner_cannot_update_receta(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $owner->id]);

        $token = $intruder->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/recetas/{$receta->id}", ['titulo' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_non_owner_cannot_delete_receta(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $receta = Receta::factory()->create(['user_id' => $owner->id]);

        $token = $intruder->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/recetas/{$receta->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_delete_any_receta(): void
    {
        // En tests hay BD aislada; hay que crear roles aqui (no usamos la BD real).
        Role::create(['name' => 'admin','guard_name' => 'sanctum']);
        Role::create(['name' => 'user', 'guard_name' => 'sanctum']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $owner = User::factory()->create();
        $owner->assignRole('user'); // si lo necesitas, crea también el rol user

        $receta = Receta::factory()->create([
            'user_id' => $owner->id,
        ]);

        $token = $admin->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/recetas/{$receta->id}")
            ->assertStatus(200);
    }
}
