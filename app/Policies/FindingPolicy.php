<?php

namespace App\Policies;

use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\FindingEvidence;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class FindingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAuditPortfolio();
    }

    public function view(User $user, Finding $finding): Response
    {
        return Gate::forUser($user)->inspect('view', $finding->audit);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('finding.create');
    }

    public function update(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.update');
    }

    public function delete(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.delete')
            && $finding->status === Finding::STATUS_OPEN;
    }

    public function respond(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.respond')
            && $this->isAuditAuditee($user, $finding);
    }

    public function comment(User $user, Finding $finding): bool
    {
        return $this->canParticipate($user, $finding);
    }

    public function viewEvidence(
        User $user,
        Finding $finding,
        FindingEvidence $evidence
    ): bool {
        return $evidence->finding_id === $finding->id
            && Gate::forUser($user)->allows('view', $finding);
    }

    public function proposeAction(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.remediate')
            && $this->isAuditAuditee($user, $finding);
    }

    public function approveAction(
        User $user,
        FindingAction $action
    ): bool {
        return $user->hasPermission('finding.update')
            && $this->isLeadAuditor($user, $action->finding);
    }

    public function rejectAction(
        User $user,
        FindingAction $action
    ): bool {
        return $user->hasPermission('finding.update')
            && $this->isLeadAuditor($user, $action->finding);
    }

    public function remediate(
        User $user,
        FindingAction $action
    ): bool {
        if (! $user->hasPermission('finding.remediate')) {
            return false;
        }

        $finding = $action->finding;

        if (! $this->isAuditAuditee($user, $finding)) {
            return false;
        }

        return $action->responsible_user_id === $user->id
            || $action->responsible_department_id === $user->department_id;
    }

    public function verify(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.verify')
            && $this->isLeadAuditor($user, $finding);
    }

    public function close(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.close')
            && $this->isLeadAuditor($user, $finding)
            && $finding->status === Finding::STATUS_CLOSURE_REQUESTED;
    }

    public function requestClosure(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.remediate')
            && $this->isAuditAuditee($user, $finding)
            && in_array($finding->status, [
                Finding::STATUS_READY_FOR_VERIFICATION,
                Finding::STATUS_CLOSURE_REJECTED,
            ], true);
    }

    public function reviewClosure(User $user, Finding $finding): bool
    {
        return $user->hasPermission('finding.close')
            && $this->isLeadAuditor($user, $finding)
            && $finding->status === Finding::STATUS_CLOSURE_REQUESTED;
    }

    private function isLeadAuditor(
        User $user,
        Finding $finding
    ): bool {
        return $finding->audit
            ->auditors()
            ->whereKey($user->id)
            ->wherePivot('assignment_type', 'LEAD_AUDITOR')
            ->exists();
    }

    private function isAuditAuditee(
        User $user,
        Finding $finding
    ): bool {
        return $user->role?->slug === 'auditee'
            && $user->department_id !== null
            && (int) $user->department_id === (int) $finding->audit->department_id;
    }

    private function canParticipate(
        User $user,
        Finding $finding
    ): bool {
        if ($this->isLeadAuditor($user, $finding)) {
            return true;
        }

        if ($this->isAuditAuditee($user, $finding)) {
            return true;
        }

        return $user->hasPermission('finding.update')
            && $finding->audit
                ->auditors()
                ->whereKey($user->id)
                ->exists();
    }
}
