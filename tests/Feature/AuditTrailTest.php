<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_changed_fields_are_recorded_with_actor_snapshot(): void
    {
        $user = $this->createUser();
        $user->update(['name' => 'Updated name']);

        app(AuditLogger::class)->recordChanges(
            'PROFILE_UPDATED',
            $user,
            $user,
            ['name' => 'Original name', 'timezone' => 'UTC'],
            ['name' => 'Updated name', 'timezone' => 'UTC']
        );

        $log = AuditLog::query()->where('action', 'PROFILE_UPDATED')->firstOrFail();

        $this->assertSame('Updated name', $log->actor_name);
        $this->assertSame($user->email, $log->actor_email);
        $this->assertSame(['name' => 'Original name'], $log->old_payload);
        $this->assertSame(['name' => 'Updated name'], $log->new_payload);
    }

    public function test_audit_creation_and_edits_record_changed_values(): void
    {
        $actor = $this->createUser();
        $auditService = app(AuditService::class);
        $audit = $auditService->create([
            'reference' => 'AUD-TRAIL-001',
            'title' => 'Initial audit title',
            'objective' => 'Review access controls.',
            'scope' => 'Selected systems.',
            'department_id' => $actor->department_id,
            'audit_mode' => 'REMOTE',
            'methodology' => 'RISK_BASED',
            'start_date' => '2026-10-01',
            'status' => 'PLANNED',
            'created_by' => $actor->id,
            'lead_auditor_id' => $actor->id,
            'supporting_auditor_ids' => [],
            'auditee_ids' => [],
        ], $actor);

        $auditService->update($audit, [
            'title' => 'Revised audit title',
            'objective' => 'Review privileged access controls.',
            'scope' => 'Selected systems and interfaces.',
            'department_id' => $actor->department_id,
            'audit_mode' => 'REMOTE',
            'methodology' => 'RISK_BASED',
            'start_date' => '2026-10-01',
            'end_date' => null,
            'lead_auditor_id' => $actor->id,
            'supporting_auditor_ids' => [],
            'auditee_ids' => [],
        ], $actor);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'AUDIT_CREATED',
            'auditable_id' => $audit->id,
            'user_id' => $actor->id,
        ]);

        $updateLog = AuditLog::query()
            ->where('action', 'AUDIT_UPDATED')
            ->where('auditable_id', $audit->id)
            ->firstOrFail();

        $this->assertSame('Initial audit title', $updateLog->old_payload['title']);
        $this->assertSame('Revised audit title', $updateLog->new_payload['title']);
        $this->assertSame('Review access controls.', $updateLog->old_payload['objective']);
        $this->assertSame('Review privileged access controls.', $updateLog->new_payload['objective']);
    }

    public function test_audit_log_entries_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $user = $this->createUser();
        $log = AuditLog::query()->create([
            'uuid' => (string) str()->uuid(),
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'actor_email' => $user->email,
            'action' => 'PROFILE_UPDATED',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_payload' => ['name' => $user->name],
            'created_at' => now(),
        ]);

        try {
            $log->update(['action' => 'ALTERED']);
            self::fail('Audit log updates should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', [
                'id' => $log->id,
                'action' => 'PROFILE_UPDATED',
            ]);
        }

        try {
            $log->delete();
            self::fail('Audit log deletions should be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseHas('audit_logs', [
                'id' => $log->id,
                'action' => 'PROFILE_UPDATED',
            ]);
        }
    }

    private function createUser(): User
    {
        $department = Department::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Audit test unit',
            'code' => Str::upper(Str::random(5)),
        ]);

        return User::factory()
            ->forRole('audit-trail-test')
            ->inDepartment($department)
            ->active()
            ->create();
    }
}
