<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    /** @param array<string, mixed>|null $oldPayload
     * @param  array<string, mixed>|null  $newPayload
     */
    public function record(
        string $action,
        Model $record,
        ?User $actor,
        ?array $oldPayload = null,
        ?array $newPayload = null
    ): void {
        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'action' => $action,
            'auditable_type' => $record::class,
            'auditable_id' => $record->getKey(),
            'old_payload' => $oldPayload,
            'new_payload' => $newPayload,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $before
     *  @param array<string, mixed> $after
     */
    public function recordChanges(
        string $action,
        Model $record,
        ?User $actor,
        array $before,
        array $after
    ): void {
        $fields = array_unique([...array_keys($before), ...array_keys($after)]);
        $oldPayload = [];
        $newPayload = [];

        foreach ($fields as $field) {
            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ($oldValue !== $newValue) {
                $oldPayload[$field] = $oldValue;
                $newPayload[$field] = $newValue;
            }
        }

        if ($oldPayload !== []) {
            $this->record($action, $record, $actor, $oldPayload, $newPayload);
        }
    }
}
