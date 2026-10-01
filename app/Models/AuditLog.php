<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
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
