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

    public function auditors(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('uuid', 'assignment_type')
            ->withTimestamps();
    }
}
