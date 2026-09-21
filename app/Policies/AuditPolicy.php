<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->hasPermission('audit.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('audit.create');
    }

    public function update(User $user, Audit $audit): bool
    {
        return $user->hasPermission('audit.update');
    }

    public function delete(User $user, Audit $audit): bool
    {
        return false;
    }
}