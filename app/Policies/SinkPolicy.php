<?php

namespace App\Policies;

use App\Models\User;

class SinkPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('read sinks');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->can('read sinks');
    }

    public function update(User $user): bool
    {
        return $user->can('edit sinks');
    }
}
