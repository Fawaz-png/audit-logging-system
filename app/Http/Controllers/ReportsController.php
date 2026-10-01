<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Finding;
use App\Models\FindingAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->hasPermission('audit.view'), 403);

        $visibleAuditIds = Audit::query()
            ->visibleTo($user)
            ->select('audits.id');
        $visibleFindings = Finding::query()
            ->whereIn('audit_id', clone $visibleAuditIds);
        $visibleActions = FindingAction::query()
            ->whereIn('finding_id', (clone $visibleFindings)->select('findings.id'));

        $summary = [
            'audit_count' => Audit::query()->visibleTo($user)->count(),
            'active_audits' => Audit::query()
                ->visibleTo($user)
                ->whereNotIn('status', ['COMPLETED', 'CLOSED', 'CANCELLED'])
                ->count(),
            'open_findings' => (clone $visibleFindings)
                ->whereNotIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED])
                ->count(),
            'high_risk_findings' => (clone $visibleFindings)
                ->whereIn('risk_rating', ['HIGH', 'CRITICAL'])
                ->whereNotIn('status', [Finding::STATUS_VERIFIED, Finding::STATUS_CLOSED])
                ->count(),
            'overdue_actions' => (clone $visibleActions)
                ->whereNotNull('agreed_due_date')
                ->whereDate('agreed_due_date', '<', today())
                ->whereNotIn('status', [FindingAction::STATUS_COMPLETED, FindingAction::STATUS_REJECTED])
                ->count(),
        ];

        $departmentExposure = (clone $visibleFindings)
            ->join('audits', 'findings.audit_id', '=', 'audits.id')
            ->join('departments', 'audits.department_id', '=', 'departments.id')
            ->select('departments.id', 'departments.name as department_name')
            ->selectRaw('COUNT(findings.id) as finding_count')
            ->selectRaw("SUM(CASE WHEN findings.risk_rating IN ('HIGH', 'CRITICAL') AND findings.status NOT IN (?, ?) THEN 1 ELSE 0 END) as high_risk_count", [
                Finding::STATUS_VERIFIED,
                Finding::STATUS_CLOSED,
            ])
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('finding_count')
            ->get();

        $riskBreakdown = (clone $visibleFindings)
            ->selectRaw('risk_rating, COUNT(*) as total')
            ->groupBy('risk_rating')
            ->orderBy('risk_rating')
            ->get();

        $statusBreakdown = (clone $visibleFindings)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return Inertia::render('Reports/Index', [
            'summary' => $summary,
            'departmentExposure' => $departmentExposure,
            'riskBreakdown' => $riskBreakdown,
            'statusBreakdown' => $statusBreakdown,
            'reportScope' => $user->role?->slug === 'auditee' ? 'department' : 'portfolio',
        ]);
    }
}
