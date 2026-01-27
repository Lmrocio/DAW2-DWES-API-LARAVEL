<?php

namespace App\Policies;

use App\Models\Receta;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RecetaPolicy
{
    // Guía docente: ver docs/04_modelos_policies_servicios.md.
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
    public function view(User $user, Receta $receta): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Receta $receta): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el dueño puede modificar su receta, además del admin.
        return $user->id === $receta->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Receta $receta): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el dueño puede borrar su receta, además del admin.
        return $user->id === $receta->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Receta $receta): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Receta $receta): bool
    {
        return false;
    }
}
