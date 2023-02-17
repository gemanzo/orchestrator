<?php

namespace App\Policies;

use App\Models\Epic;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class EpicPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return UserRole::Admin || UserRole::Developer;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Epic $epic)
    {
        return UserRole::Admin || UserRole::Developer;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return UserRole::Admin || UserRole::Developer;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Epic $epic)
    {
        return UserRole::Admin || UserRole::Developer;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Epic $epic)
    {
        return $user->hasRole(UserRole::Admin) || $user->hasRole(UserRole::Developer);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Epic $epic)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Epic  $epic
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Epic $epic)
    {
        //
    }
}
