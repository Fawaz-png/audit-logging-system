<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FindingEvidence extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'finding_evidences';

    public const TYPE_FINDING = 'FINDING';

    public const TYPE_RESOLUTION = 'RESOLUTION';

    protected $fillable = [
        'finding_id',
        'type',
        'uploaded_by',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'description',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
