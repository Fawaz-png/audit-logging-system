<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAuditPortfolio();
    }

    public function view(User $user, Audit $audit): Response
    {
        $isVisible = Audit::query()
            ->visibleTo($user)
            ->whereKey($audit->getKey())
            ->exists();

        return $isVisible
            ? Response::allow()
            : Response::denyAsNotFound();
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
