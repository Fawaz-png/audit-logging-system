<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\User;
use App\Notifications\FindingWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FindingWorkflowNotifier
{
    public function notifyDepartmentAuditees(
        Finding $finding,
        User $actor,
        string $title,
        string $message
    ): void {
        $recipients = User::query()
            ->where('active', true)
            ->where('department_id', $finding->audit->department_id)
            ->whereHas('role', fn ($query) => $query->where('slug', 'auditee'))
            ->get();

        $this->send($finding, $actor, $recipients, $title, $message);
    }

    public function notifyParticipants(
        Finding $finding,
        User $actor,
        string $title,
        string $message
    ): void {
        $finding->loadMissing('audit');

        $recipients = $finding->audit->auditors()
            ->where('active', true)
            ->get()
            ->concat(User::query()
                ->where('active', true)
                ->where('department_id', $finding->audit->department_id)
                ->whereHas('role', fn ($query) => $query->where('slug', 'auditee'))
                ->get());

        $this->send($finding, $actor, $recipients, $title, $message);
    }

    public function notifyLeadAuditors(
        Finding $finding,
        User $actor,
        string $title,
        string $message
    ): void {
        $recipients = $finding->audit->auditors()
            ->where('active', true)
            ->wherePivot('assignment_type', 'LEAD_AUDITOR')
            ->get();

        $this->send($finding, $actor, $recipients, $title, $message);
    }

    /** @param Collection<int, User> $recipients */
    private function send(
        Finding $finding,
        User $actor,
        Collection $recipients,
        string $title,
        string $message
    ): void {
        $notifications = $recipients
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->is($actor))
            ->values();

        DB::afterCommit(function () use ($finding, $notifications, $title, $message): void {
            foreach ($notifications as $recipient) {
                $recipient->notify(new FindingWorkflowNotification(
                    $title,
                    $message,
                    route('findings.show', $finding),
                    $finding->reference,
                    $finding->id
                ));
            }
        });
    }
}
