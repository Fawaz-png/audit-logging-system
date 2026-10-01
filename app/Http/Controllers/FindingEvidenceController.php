<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\FindingEvidence;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FindingEvidenceController extends Controller
{
    public function download(
        Finding $finding,
        FindingEvidence $evidence
    ): StreamedResponse {
        abort_unless($evidence->finding_id === $finding->id, 404);
        $this->authorize('viewEvidence', [$finding, $evidence]);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($evidence->disk);
        abort_unless($disk->exists($evidence->path), 404);

        $downloadName = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '_',
            basename($evidence->original_name)
        ) ?: 'evidence';

        return $disk->download($evidence->path, $downloadName, [
            'Content-Type' => $evidence->mime_type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
