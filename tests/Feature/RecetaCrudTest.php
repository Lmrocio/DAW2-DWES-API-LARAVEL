<?php

namespace Tests\Feature;

use App\Models\Receta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecetaCrudTest extends TestCase
{
    use RefreshDatabase;
    // Guía docente: ver docs/06_tests.md.

    private function authUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        return [$user, $token];
    }

    public function test_authenticated_user_can_create_receta(): void
    {
        [, $token] = $this->authUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/recetas', [
                'titulo' => 'Tortilla de patatas',
                'descripcion' => 'Receta clásica',
                'instrucciones' => 'Huevos, patatas y aceite',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'titulo' => 'Tortilla de patatas',
            ]);

        $this->assertDatabaseHas('recetas', [
            'titulo' => 'Tortilla de patatas',
        ]);
    }

    public function test_can_list_recetas(): void
    {
        Receta::factory()->count(3)->create();

        [$user, $token] = $this->authUser();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/recetas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_view_single_receta(): void
    {
        $receta = Receta::factory()->create();

        [, $token] = $this->authUser();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/recetas/{$receta->id}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'titulo' => $receta->titulo,
            ]);
    }

    public function test_owner_can_update_non_published_receta(): void
    {
        [$owner, $token] = $this->authUser();

        $receta = Receta::factory()->create([
            'user_id' => $owner->id,
            'publicada' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/recetas/{$receta->id}", [
                'titulo' => 'Título actualizado',
            ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'titulo' => 'Título actualizado',
            ]);
    }

    public function test_cannot_update_published_receta(): void
    {
        [$owner, $token] = $this->authUser();

        $receta = Receta::factory()->create([
            'user_id' => $owner->id,
            'publicada' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/recetas/{$receta->id}", [
                'titulo' => 'Intento ilegal',
            ])
            ->assertJsonFragment([
                'code' => 'RECETA_PUBLICADA',
            ])
            ->assertJsonStructure([
                'error' => ['code', 'message'],
            ]);
    }

    public function test_owner_can_delete_receta(): void
    {
        [$owner, $token] = $this->authUser();

        $receta = Receta::factory()->create([
            'user_id' => $owner->id,
            'publicada' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/recetas/{$receta->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('recetas', [
            'id' => $receta->id,
        ]);
    }

    public function test_non_owner_cannot_delete_receta(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $receta = Receta::factory()->create([
            'user_id' => $owner->id,
        ]);

        $token = $intruder->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/recetas/{$receta->id}")
            ->assertStatus(403);
    }

    public function test_can_paginate_with_custom_page_size(): void
    {
        User::factory()->create();
        Receta::factory()->count(30)->create();

        $token = User::first()->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/recetas?per_page=5')
            ->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_can_sort_recetas_by_title(): void
    {
        $user = User::factory()->create();

        Receta::factory()->create(['titulo' => 'Zeta']);
        Receta::factory()->create(['titulo' => 'Alpha']);

        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/recetas?sort=titulo');

        $response->assertJsonPath('data.0.titulo', 'Alpha');
    }

    public function test_can_search_recetas_by_text(): void
    {
        $user = User::factory()->create();

        Receta::factory()->create(['titulo' => 'Tortilla de patatas']);
        Receta::factory()->create(['titulo' => 'Gazpacho']);

        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/recetas?q=tortilla')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
