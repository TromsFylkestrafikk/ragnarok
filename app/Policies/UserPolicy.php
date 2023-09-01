<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     * For the index() function in UserAccountController.php
     */
    public function viewAny(/*User $user*/): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * For the show() function in UserAccountController.php
     */
    public function view(/*User $user*/): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * For the store() function in UserAccountController.php
     */
    public function create(User $user): bool
    {
        return $user->can('create users');
    }

    /**
     * Determine whether the user can update the model.
     * For the update() function in UserAccountController.php
     */
    public function update(User $user, User $userAccount): bool
    {
        return $user->can('edit users') && ($user->id !== $userAccount->id);
    }

    /**
     * Determine whether the user can delete the model.
     * For the destroy() function in UserAccountController.php
     */
    public function delete(User $user, User $userAccount): bool
    {
        return $user->can('delete users') && ($user->id !== $userAccount->id);
    }

    /**
     * Determine whether the user can restore the model.
     * Not in use.
     */
    public function restore(/*User $user, User $userAccount*/): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Not in use.
     */
    public function forceDelete(/*User $user, User $userAccount*/): bool
    {
        return false;
    }
}
