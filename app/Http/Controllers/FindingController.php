<?php

namespace App\Http\Controllers;

use App\Http\Requests\Findings\AddFindingCommentRequest;
use App\Http\Requests\Findings\ApproveCorrectiveActionRequest;
use App\Http\Requests\Findings\ProposeCorrectiveActionRequest;
use App\Http\Requests\Findings\RejectCorrectiveActionRequest;
use App\Http\Requests\Findings\RespondToFindingRequest;
use App\Http\Requests\StoreFindingRequest;
use App\Http\Requests\UpdateFindingRequest;
use App\Models\Audit;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingAction;
use App\Models\FindingEvidence;
use App\Models\User;
use App\Services\FindingEvidenceService;
use App\Services\FindingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FindingController extends Controller
{
    public function __construct(
        private FindingService $findingService
    ) {}

    public function create(Audit $audit): Response
    {
        $this->authorize('create', Finding::class);
        $this->authorize('view', $audit);

        $audit->load([
            'department',
            'auditors',
        ]);

        return Inertia::render('Findings/Create', [
            'audit' => $audit,
        ]);
    }

    public function store(
        StoreFindingRequest $request,
        Audit $audit,
        FindingEvidenceService $evidenceService
    ) {
        $this->authorize('create', Finding::class);
        $this->authorize('view', $audit);

        if (! in_array($audit->status, ['COMPLETED', 'CLOSED'], true)) {
            throw ValidationException::withMessages([
                'audit' => 'Findings can only be registered after the audit is concluded.',
            ]);
        }

        $data = $request->validated();

        $finding = DB::transaction(function () use ($request, $audit, $data, $evidenceService): Finding {
            $finding = $this->findingService->create(
                $audit,
                $data,
                $request->user()
            );

            $evidenceService->store(
                $finding,
                $request->user(),
                [
                    'type' => FindingEvidence::TYPE_FINDING,
                    'description' => $data['evidence_description'] ?? null,
                ],
                $request->file('evidence_file')
            );

            return $finding;
        });

        return redirect()->route('findings.show', $finding);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Finding::class);

        $visibleAuditIds = Audit::query()
            ->visibleTo($request->user())
            ->select('audits.id');

        return Inertia::render('Findings/Index', [
            'findings' => Finding::query()
                ->whereIn('audit_id', $visibleAuditIds)
                ->with(['audit:id,reference,title,department_id', 'audit.department:id,name'])
                ->latest('updated_at')
                ->paginate(25),
        ]);
    }

    public function show(Request $request, Finding $finding): Response
    {
        $this->authorize('view', $finding);

        $finding->load([
            'audit',
            'audit.department',
            'audit.auditors',
            'actions.responsibleDepartment',
            'actions.responsibleUser',
            'actions.approver',
            'creator',
            'updater',
            'responseBy',
            'verifier',
            'closer',
            'closureRequester',
            'closureReviewer',
            'comments.user',
            'evidences.uploader',
        ]);

        $departments = Department::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);

        $users = User::query()
            ->where('active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'department_id',
            ]);

        return Inertia::render('Findings/Show', [
            'finding' => $finding,
            'departments' => $departments,
            'users' => $users,
            'canRequestClosure' => $request->user()->can('requestClosure', $finding),
            'canReviewClosure' => $request->user()->can('reviewClosure', $finding),
            'canComment' => $request->user()->can('comment', $finding),
        ]);
    }

    public function edit(Finding $finding): Response
    {
        $this->authorize('update', $finding);

        $finding->load([
            'audit',
        ]);

        return Inertia::render('Findings/Edit', [
            'finding' => $finding,
        ]);
    }

    public function update(
        UpdateFindingRequest $request,
        Finding $finding
    ) {
        $this->authorize('update', $finding);

        $finding = $this->findingService->update(
            $finding,
            $request->validated(),
            $request->user()
        );

        return redirect()->route('findings.show', $finding);
    }

    public function destroy(Finding $finding)
    {
        $this->authorize('delete', $finding);

        $this->findingService->delete($finding);

        return redirect()->route('audits.show', $finding->audit_id);
    }

    public function respond(
        RespondToFindingRequest $request,
        Finding $finding
    ) {
        $this->authorize('respond', $finding);

        $finding = $this->findingService->respond(
            $finding,
            $request->user(),
            $request->validated('response')
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with('success', 'Finding response submitted.');
    }

    public function comment(
        AddFindingCommentRequest $request,
        Finding $finding
    ) {
        $this->authorize('comment', $finding);

        $this->findingService->addComment(
            $finding,
            $request->user(),
            $request->validated('body')
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with('success', 'Comment added.');
    }

    public function proposeAction(
        ProposeCorrectiveActionRequest $request,
        Finding $finding
    ) {
        $this->authorize('proposeAction', $finding);

        $this->findingService->proposeAction(
            $finding,
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with(
                'success',
                'Corrective action submitted for auditor approval.'
            );
    }

    public function approveAction(
        ApproveCorrectiveActionRequest $request,
        FindingAction $action
    ) {
        $this->authorize('approveAction', $action);

        $this->findingService->approveAction(
            $action,
            $request->user(),
            $request->validated('agreed_due_date'),
            $request->validated('comment')
        );

        return redirect()
            ->route('findings.show', $action->finding_id)
            ->with(
                'success',
                'Corrective action approved and remediation started.'
            );
    }

    public function rejectAction(
        RejectCorrectiveActionRequest $request,
        FindingAction $action
    ) {
        $this->authorize('rejectAction', $action);

        $this->findingService->rejectAction(
            $action,
            $request->user(),
            $request->validated('reason')
        );

        return redirect()
            ->route('findings.show', $action->finding_id)
            ->with(
                'success',
                'Corrective action returned for further discussion.'
            );
    }

    public function startRemediation(
        FindingAction $action
    ) {
        $this->authorize('remediate', $action);

        $this->findingService->startRemediation(
            $action,
            request()->user()
        );

        return redirect()
            ->route('findings.show', $action->finding_id)
            ->with('success', 'Remediation started.');
    }

    public function completeRemediation(
        FindingAction $action
    ) {
        $this->authorize('remediate', $action);

        $this->findingService->completeRemediation(
            $action,
            request()->user()
        );

        return redirect()
            ->route('findings.show', $action->finding_id)
            ->with(
                'success',
                'Remediation marked complete and submitted for verification.'
            );
    }
}
