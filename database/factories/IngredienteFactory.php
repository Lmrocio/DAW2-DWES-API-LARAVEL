<?php

namespace Database\Factories;

use App\Models\Ingrediente;
use App\Models\Receta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingrediente>
 */
class IngredienteFactory extends Factory
{
    protected $model = Ingrediente::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ingredientes = [
            ['nombre' => 'Huevo', 'cantidad' => '2-3', 'unidad' => 'ud'],
            ['nombre' => 'Harina', 'cantidad' => '200', 'unidad' => 'g'],
            ['nombre' => 'Leche', 'cantidad' => '250', 'unidad' => 'ml'],
            ['nombre' => 'Azúcar', 'cantidad' => '100', 'unidad' => 'g'],
            ['nombre' => 'Sal', 'cantidad' => '1', 'unidad' => 'pizca'],
            ['nombre' => 'Aceite de oliva', 'cantidad' => '3', 'unidad' => 'cucharadas'],
            ['nombre' => 'Patata', 'cantidad' => '500', 'unidad' => 'g'],
            ['nombre' => 'Cebolla', 'cantidad' => '1', 'unidad' => 'ud'],
            ['nombre' => 'Ajo', 'cantidad' => '2', 'unidad' => 'dientes'],
            ['nombre' => 'Tomate', 'cantidad' => '300', 'unidad' => 'g'],
        ];

        $ingrediente = fake()->randomElement($ingredientes);

        return [
            'receta_id' => Receta::factory(),
            'nombre' => $ingrediente['nombre'],
            'cantidad' => $ingrediente['cantidad'],
            'unidad' => $ingrediente['unidad'],
        ];
    }
}
