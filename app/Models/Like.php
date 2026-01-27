<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receta_id',
    ];

    /**
     * Relación: un like pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: un like pertenece a una receta
     */
    public function receta()
    {
        return $this->belongsTo(Receta::class);
    }
}
