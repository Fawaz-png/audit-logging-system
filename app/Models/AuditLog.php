<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Audit log records cannot be updated.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Audit log records cannot be deleted through the model.');
        });
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'actor_name',
        'actor_email',
        'action',
        'auditable_type',
        'auditable_id',
        'old_payload',
        'new_payload',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'old_payload' => 'array',
            'new_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
