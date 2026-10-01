<?php

namespace App\Http\Controllers;

use App\Http\Requests\Findings\ReviewFindingClosureRequest;
use App\Http\Requests\Findings\StoreFindingClosureRequest;
use App\Models\Finding;
use App\Services\FindingClosureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class FindingClosureController extends Controller
{
    public function store(
        StoreFindingClosureRequest $request,
        Finding $finding,
        FindingClosureService $closureService
    ): RedirectResponse {
        $this->authorize('requestClosure', $finding);

        $closureService->request(
            $finding,
            $request->user(),
            $request->validated(),
            $request->file('file')
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with('success', 'Closure request submitted with resolution evidence.');
    }

    public function approve(
        ReviewFindingClosureRequest $request,
        Finding $finding,
        FindingClosureService $closureService
    ): RedirectResponse {
        $this->authorize('reviewClosure', $finding);

        $closureService->approve(
            $finding,
            $request->user(),
            $request->validated('notes')
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with('success', 'Finding closure approved.');
    }

    public function reject(
        ReviewFindingClosureRequest $request,
        Finding $finding,
        FindingClosureService $closureService
    ): RedirectResponse {
        $this->authorize('reviewClosure', $finding);

        $reason = trim((string) $request->validated('notes'));

        if ($reason === '') {
            throw ValidationException::withMessages([
                'notes' => 'Provide a reason for rejecting the closure request.',
            ]);
        }

        $closureService->reject(
            $finding,
            $request->user(),
            $reason
        );

        return redirect()
            ->route('findings.show', $finding)
            ->with('success', 'Closure request returned to the auditee.');
    }
}
