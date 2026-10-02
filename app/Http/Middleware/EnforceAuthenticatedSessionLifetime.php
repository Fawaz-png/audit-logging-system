<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceAuthenticatedSessionLifetime
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $session = $request->session();
        $now = now()->timestamp;
        $startedAt = (int) $session->get('auth_session_started_at', $now);
        $lastActivityAt = (int) $session->get('auth_session_last_activity_at', $now);
        $sessionVersion = (int) $session->get('auth_session_version', 1);
        $absoluteTimeout = (int) config('auth.session.absolute_timeout', 720) * 60;
        $idleTimeout = (int) config('auth.session.idle_timeout', 30) * 60;

        if (
            ! $user->active
            || $sessionVersion !== (int) $user->session_version
            || $now - $startedAt >= $absoluteTimeout
            || $now - $lastActivityAt >= $idleTimeout
        ) {
            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Your session ended. Sign in again to continue.');
        }

        $session->put('auth_session_started_at', $startedAt);
        $session->put('auth_session_last_activity_at', $now);
        $session->put('auth_session_version', (int) $user->session_version);

        return $next($request);
    }
}
