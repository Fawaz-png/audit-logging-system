<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('audit-log.view'), 403);

        $search = $request->string('search')->trim()->toString();

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('auditable_type', 'like', '%'.$search.'%')
                        ->orWhere('auditable_id', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        })
                        ->orWhere('new_payload->reference', 'like', '%'.$search.'%')
                        ->orWhere('new_payload->title', 'like', '%'.$search.'%')
                        ->orWhere('old_payload->reference', 'like', '%'.$search.'%')
                        ->orWhere('old_payload->title', 'like', '%'.$search.'%');
                });
            })
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(static fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->only(['name', 'email']),
                'target' => [
                    'type' => class_basename($log->auditable_type),
                    'id' => $log->auditable_id,
                    'reference' => data_get($log->new_payload, 'reference')
                        ?? data_get($log->old_payload, 'reference'),
                ],
                'created_at' => $log->created_at,
            ]);

        return Inertia::render('AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }
}
