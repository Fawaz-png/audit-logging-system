<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuditRequest;
use App\Http\Requests\UpdateAuditRequest;
use App\Models\Audit;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Audit::class);

        return Inertia::render('Audits/Index', [
            'audits' => Audit::query()
                ->visibleTo($request->user())
                ->with([
                    'department',
                    'creator',
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Audit::class);

        return Inertia::render('Audits/Create', [
            'departments' => Department::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'code',
                ]),

            'users' => User::query()
                ->where('active', true)
                ->with('role:id,name,slug')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                    'department_id',
                    'role_id',
                ]),
        ]);
    }

    public function store(
        StoreAuditRequest $request,
        AuditService $auditService
    ): RedirectResponse {
        $this->authorize('create', Audit::class);

        $audit = $auditService->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('audits.show', $audit);
    }

    public function show(Audit $audit): Response
    {
        $this->authorize('view', $audit);

        return Inertia::render('Audits/Show', [
            'audit' => $audit->load([
                'creator',
                'department',
                'auditors',
                'findings',
            ]),
        ]);
    }

    public function edit(Audit $audit): Response
    {
        $this->authorize('update', $audit);

        return Inertia::render('Audits/Edit', [
            'audit' => $audit->load([
                'creator',
                'department',
                'auditors',
            ]),

            'departments' => Department::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'code',
                ]),

            'users' => User::query()
                ->where('active', true)
                ->with('role:id,name,slug')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'email',
                    'department_id',
                    'role_id',
                ]),
        ]);
    }

    public function update(
        UpdateAuditRequest $request,
        Audit $audit,
        AuditService $auditService
    ): RedirectResponse {
        $this->authorize('update', $audit);

        $auditService->update(
            $audit,
            $request->validated()
        );

        return redirect()
            ->route('audits.show', $audit)
            ->with('success', 'Audit updated successfully.');
    }
}
