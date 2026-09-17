<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditRequest;
use App\Models\Audit;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class AuditController extends Controller
{
    public function store(
        StoreAuditRequest $request,
        AuditService $auditService
    ): RedirectResponse {
        $this->authorize('create', Audit::class);

        $audit = $auditService->create(
            [
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('audits.show', $audit);
    }
}