<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Audit extends Model
{
    //
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'reference',
        'title',
        'objective',
        'scope',
        'department_id',
        'audit_mode',
        'location',
        'methodology',
        'previous_audit_reference',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('uuid', 'assignment_type')
            ->withTimestamps();
    }
}
