<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('user.manage');
    }
}
