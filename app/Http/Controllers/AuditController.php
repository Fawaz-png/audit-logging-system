<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditRequest;
use App\Models\Audit;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{

    public function create(): Response
    {
        $this->authorize("create", Audit::class);
        return Inertia::render("Audits/Create");
    }
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

    public function show(Audit $audit): Response
    {
        return Inertia::render('Audits/Show', [
            'audit' => $audit->load('creator'),
        ]);
    }
}