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
}
