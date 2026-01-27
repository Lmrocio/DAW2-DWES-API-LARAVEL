<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComentarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comentario $comentario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Cualquier usuario autenticado puede comentar
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Solo el autor o admin pueden actualizar un comentario
     */
    public function update(User $user, Comentario $comentario): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el autor del comentario puede modificarlo
        return $user->id === $comentario->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Solo el autor o admin pueden borrar un comentario
     */
    public function delete(User $user, Comentario $comentario): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el autor del comentario puede borrarlo
        return $user->id === $comentario->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comentario $comentario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comentario $comentario): bool
    {
        return false;
    }
}
