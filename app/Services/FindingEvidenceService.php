<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FindingEvidenceService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function store(
        Finding $finding,
        User $user,
        array $data,
        UploadedFile $file
    ): FindingEvidence {
        $disk = 'local';
        $path = $file->store('finding-evidence/'.$finding->uuid, $disk);

        if (! is_string($path)) {
            throw new \RuntimeException('The evidence file could not be stored.');
        }

        try {
            $evidence = DB::transaction(fn (): FindingEvidence => $finding->evidences()->create([
                'uuid' => (string) Str::uuid(),
                'type' => $data['type'],
                'uploaded_by' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => $disk,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'description' => $data['description'] ?? null,
            ]));

            $this->auditLogger->record(
                $data['type'] === FindingEvidence::TYPE_RESOLUTION
                    ? 'RESOLUTION_EVIDENCE_UPLOADED'
                    : 'FINDING_EVIDENCE_UPLOADED',
                $evidence,
                $user,
                null,
                [
                    'finding_id' => $finding->id,
                    'original_name' => $evidence->original_name,
                    'size' => $evidence->size,
                    'mime_type' => $evidence->mime_type,
                ]
            );

            return $evidence;
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
    }
}
