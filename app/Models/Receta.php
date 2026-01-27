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
        'imagen_url',
    ];

    /**
     * Atributos que deben ser añadidos a la serialización del modelo
     */
    protected $appends = ['likes_count', 'imagen_url_completa'];

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
     * Relación: una receta tiene muchos likes (1:N)
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
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

    /**
     * Relación: una receta tiene muchos comentarios
     */
    public function comentarios()
    {
        return $this->hasMany(Comentario::class);
    }

    /**
     * Accessor: URL completa de la imagen del plato
     * Devuelve la URL absoluta y accesible desde la API
     */
    public function getImagenUrlCompletaAttribute(): ?string
    {
        if (!$this->imagen_url) {
            return null;
        }

        // Si ya es una URL completa (http/https), devolverla tal cual
        if (str_starts_with($this->imagen_url, 'http')) {
            return $this->imagen_url;
        }

        // Construir URL absoluta usando asset() que genera URL completa
        return asset('storage/' . $this->imagen_url);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (filtros reutilizables)
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Filtrar recetas por ingrediente (que contenga un texto)
     *
     * Ejemplo: Receta::conIngrediente('huevo')->get()
     */
    public function scopeConIngrediente($query, ?string $ingrediente)
    {
        if (!$ingrediente) {
            return $query;
        }

        return $query->whereHas('ingredientes', function ($q) use ($ingrediente) {
            $q->where('nombre', 'ILIKE', "%{$ingrediente}%");
        });
    }

    /**
     * Scope: Ordenar por popularidad (número de likes descendente)
     *
     * Ejemplo: Receta::porPopularidad()->get()
     */
    public function scopePorPopularidad($query)
    {
        return $query->withCount('likes')
            ->orderBy('likes_count', 'desc');
    }

    /**
     * Scope: Búsqueda general en título y descripción
     *
     * Ejemplo: Receta::buscar('paella')->get()
     */
    public function scopeBuscar($query, ?string $termino)
    {
        if (!$termino) {
            return $query;
        }

        return $query->where(function ($q) use ($termino) {
            $q->where('titulo', 'ILIKE', "%{$termino}%")
                ->orWhere('descripcion', 'ILIKE', "%{$termino}%");
        });
    }

    /**
     * Scope: Ordenar por campo permitido
     *
     * Ejemplo: Receta::ordenarPor('titulo', 'desc')->get()
     */
    public function scopeOrdenarPor($query, ?string $campo, string $direccion = 'asc')
    {
        if (!$campo) {
            return $query;
        }

        $camposPermitidos = ['titulo', 'created_at', 'likes_count'];

        if (!in_array($campo, $camposPermitidos)) {
            return $query;
        }

        // Si ordena por likes_count, necesita withCount
        if ($campo === 'likes_count' && !$query->getQuery()->columns) {
            $query->withCount('likes');
        }

        return $query->orderBy($campo, $direccion);
    }
}
