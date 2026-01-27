<?php

namespace Tests\Unit;

use App\Models\Receta;
use App\Services\RecetaService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecetaServiceTest extends TestCase
{
    use RefreshDatabase;
    // Guía docente: ver docs/06_tests.md.

    public function test_receta_no_publicada_puede_modificarse(): void
    {
        $receta = Receta::factory()->create([
            'publicada' => false,
        ]);

        $service = new RecetaService();

        $this->expectNotToPerformAssertions();
        $service->assertCanBeModified($receta);
    }

    public function test_receta_publicada_no_puede_modificarse(): void
    {
        $receta = Receta::factory()->create([
            'publicada' => true,
        ]);

        $service = new RecetaService();

        $this->expectException(DomainException::class);
        $service->assertCanBeModified($receta);
    }
}
