<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Finding;
use App\Models\FindingAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $visibleAuditIds = Audit::query()
            ->visibleTo($user)
            ->select('audits.id');
        $visibleFindings = Finding::query()
            ->whereIn('audit_id', $visibleAuditIds);
        $visibleActions = FindingAction::query()
            ->whereIn('finding_id', (clone $visibleFindings)->select('findings.id'));

        $stats = [
            'activeAudits' => Audit::query()
                ->visibleTo($user)
                ->whereNotIn('status', ['CLOSED', 'COMPLETED', 'CANCELLED'])
                ->count(),
            'openFindings' => (clone $visibleFindings)
                ->whereNotIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED])
                ->count(),
            'pendingResponses' => (clone $visibleFindings)
                ->where('status', Finding::STATUS_OPEN)
                ->count(),
            'pendingClosureRequests' => (clone $visibleFindings)
                ->where('status', Finding::STATUS_CLOSURE_REQUESTED)
                ->count(),
            'overdueActions' => (clone $visibleActions)
                ->whereNotNull('agreed_due_date')
                ->whereDate('agreed_due_date', '<', today())
                ->whereNotIn('status', [FindingAction::STATUS_COMPLETED, FindingAction::STATUS_REJECTED])
                ->count(),
            'highRiskFindings' => (clone $visibleFindings)
                ->whereIn('risk_rating', ['HIGH', 'CRITICAL'])
                ->whereNotIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED])
                ->count(),
        ];

        $recentFindings = (clone $visibleFindings)
            ->with(['audit:id,reference,title', 'audit.department:id,name'])
            ->latest('updated_at')
            ->limit(6)
            ->get([
                'id',
                'audit_id',
                'reference',
                'title',
                'risk_rating',
                'status',
                'updated_at',
            ]);

        $overdueActions = (clone $visibleActions)
            ->with('finding:id,reference,title')
            ->whereNotNull('agreed_due_date')
            ->whereDate('agreed_due_date', '<', today())
            ->whereNotIn('status', [FindingAction::STATUS_COMPLETED, FindingAction::STATUS_REJECTED])
            ->orderBy('agreed_due_date')
            ->limit(5)
            ->get(['id', 'finding_id', 'action', 'agreed_due_date', 'status']);

        $statusBreakdown = (clone $visibleFindings)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentFindings' => $recentFindings,
            'overdueActions' => $overdueActions,
            'statusBreakdown' => $statusBreakdown,
            'dashboardScope' => $user->role?->slug === 'auditee'
                ? 'department'
                : 'portfolio',
        ]);
    }
}
