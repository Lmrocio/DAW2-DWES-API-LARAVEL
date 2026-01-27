<?php

namespace Database\Factories;

use App\Models\Comentario;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comentario>
 */
class ComentarioFactory extends Factory
{
    protected $model = Comentario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comentarios = [
            '¡Excelente receta! La he probado y quedó deliciosa.',
            'Muy buena, aunque yo le añadiría un poco más de sal.',
            'Perfecta para una cena familiar.',
            'La preparé el fin de semana y fue todo un éxito.',
            'Me encanta esta receta, la recomiendo 100%.',
            'Fácil de hacer y con resultados profesionales.',
            'Una receta clásica que nunca falla.',
            'La mejor que he probado hasta ahora.',
            'Mis hijos la adoran, la hago cada semana.',
            'Sencilla pero deliciosa.',
        ];

        return [
            'user_id' => User::factory(),
            'receta_id' => Receta::factory(),
            'texto' => fake()->randomElement($comentarios),
        ];
    }
}
