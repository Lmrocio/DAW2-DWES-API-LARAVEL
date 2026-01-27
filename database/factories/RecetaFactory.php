<?php

namespace Database\Factories;

use App\Models\Receta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receta>
 */
class RecetaFactory extends Factory
{
    // Guía docente: ver docs/05_base_de_datos.md.

    protected $model = Receta::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titulo' => $this->faker->sentence(4),
            'descripcion' => $this->faker->sentence(10),
            'instrucciones' => $this->faker->paragraph(4),
        ];
    }
}
