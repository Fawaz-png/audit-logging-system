<?php

namespace App\Policies;

use App\Models\FindingAction;
use App\Models\User;

class FindingActionPolicy
{
    public function approveAction(User $user, FindingAction $action): bool
    {
        return $user->hasPermission('finding.update')
            && $action->finding->audit
                ->auditors()
                ->whereKey($user->id)
                ->wherePivot('assignment_type', 'LEAD_AUDITOR')
                ->exists();
    }

    public function rejectAction(User $user, FindingAction $action): bool
    {
        return $this->approveAction($user, $action);
    }

    public function remediate(User $user, FindingAction $action): bool
    {
        if (! $user->hasPermission('finding.remediate')) {
            return false;
        }

        $finding = $action->finding;

        if (! $user->department_id) {
            return false;
        }

        return $finding->audit
            ->auditors()
            ->whereKey($user->id)
            ->wherePivot('assignment_type', 'AUDITEE')
            ->where('department_id', $user->department_id)
            ->exists()
            && (
                $action->responsible_user_id === $user->id
                || $action->responsible_department_id === $user->department_id
            );
    }
}
