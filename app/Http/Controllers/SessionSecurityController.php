<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionSecurityController extends Controller
{
    public function revokeOtherSessions(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $request, $auditLogger): void {
            $user = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            $user->increment('session_version');
            $user->refresh();

            $request->session()->put('auth_session_version', (int) $user->session_version);

            $auditLogger->record(
                'OTHER_SESSIONS_REVOKED',
                $user,
                $user,
                null,
                ['session_version' => (int) $user->session_version]
            );
        });

        return back()->with('success', 'Other signed-in sessions have been revoked.');
    }
}
