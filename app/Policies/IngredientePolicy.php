<?php

namespace App\Policies;

use App\Models\Ingrediente;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IngredientePolicy
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
    public function view(User $user, Ingrediente $ingrediente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Solo el dueño de la receta o admin puede agregar ingredientes
     */
    public function create(User $user): bool
    {
        // La autorización real se hará en el controller verificando la receta
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * Solo el dueño de la receta o admin puede modificar ingredientes
     */
    public function update(User $user, Ingrediente $ingrediente): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el dueño de la receta puede modificar ingredientes
        return $user->id === $ingrediente->receta->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Solo el dueño de la receta o admin puede borrar ingredientes
     */
    public function delete(User $user, Ingrediente $ingrediente): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // Solo el dueño de la receta puede borrar ingredientes
        return $user->id === $ingrediente->receta->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ingrediente $ingrediente): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ingrediente $ingrediente): bool
    {
        return false;
    }
}
