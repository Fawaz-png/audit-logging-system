<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Finding extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'audit_id',
        'reference',
        'title',
        'description',
        'criteria',
        'effect',
        'recommendation',
        'process',
        'risk_rating',
        'status',
        'auditee_response',
        'response_by',
        'response_at',
        'verified_by',
        'verified_at',
        'verification_notes',
        'closed_by',
        'closed_at',
        'closure_requested_by',
        'closure_requested_at',
        'closure_reviewed_by',
        'closure_request_notes',
        'closure_reviewed_at',
        'closure_review_notes',
        'closure_rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'response_at' => 'datetime',
            'verified_at' => 'datetime',
            'closed_at' => 'datetime',
            'closure_requested_at' => 'datetime',
            'closure_reviewed_at' => 'datetime',
        ];
    }

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_RESPONSE_RECEIVED = 'RESPONSE_RECEIVED';

    public const STATUS_ACTION_PENDING_APPROVAL = 'ACTION_PENDING_APPROVAL';

    public const STATUS_REMEDIATION_IN_PROGRESS = 'REMEDIATION_IN_PROGRESS';

    public const STATUS_READY_FOR_VERIFICATION = 'READY_FOR_VERIFICATION';

    public const STATUS_CLOSURE_REQUESTED = 'CLOSURE_REQUESTED';

    public const STATUS_CLOSURE_REJECTED = 'CLOSURE_REJECTED';

    public const STATUS_VERIFIED = 'VERIFIED';

    public const STATUS_CLOSED = 'CLOSED';

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(FindingAction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function latestAction(): HasOne
    {
        return $this->hasOne(FindingAction::class)->latestOfMany();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FindingComment::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(FindingEvidence::class);
    }

    public function findingEvidences(): HasMany
    {
        return $this->hasMany(FindingEvidence::class)
            ->where('type', FindingEvidence::TYPE_FINDING);
    }

    public function resolutionEvidences(): HasMany
    {
        return $this->hasMany(FindingEvidence::class)
            ->where('type', FindingEvidence::TYPE_RESOLUTION);
    }

    public function responseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'response_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function closureRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_requested_by');
    }

    public function closureReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closure_reviewed_by');
    }
}
