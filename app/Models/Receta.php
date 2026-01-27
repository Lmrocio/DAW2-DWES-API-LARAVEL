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

    /**
     * Atributos que deben ser añadidos a la serialización del modelo
     */
    protected $appends = ['likes_count'];

    // Relación inversa: una receta pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relación: una receta tiene muchos ingredientes
     */
    public function ingredientes()
    {
        return $this->hasMany(Ingrediente::class);
    }

    /**
     * Relación: una receta tiene muchos likes (N:M con users)
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Relación: usuarios que han dado like a esta receta
     */
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'likes')
            ->withTimestamps();
    }

    /**
     * Atributo calculado: número de likes
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    /**
     * Verificar si un usuario ha dado like a esta receta
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
