<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    // Guía docente: ver docs/04_modelos_policies_servicios.md.
    /** @use HasFactory<\Database\Factories\RecetaFactory> */
    use HasFactory;


    // El atributo protected $fillable sirve para definir qué campos de la tabla
    // pueden ser asignados masivamente (mass assignment). Esto es importante
    // para proteger contra asignaciones no deseadas o maliciosas cuando se crean
    // o actualizan registros en la base de datos.
    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'instrucciones',
        'publicada',
    ];

    // Relación inversa: una receta pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
