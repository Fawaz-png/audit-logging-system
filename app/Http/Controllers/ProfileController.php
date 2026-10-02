<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'preferences' => $user->preferences ?? [
                    'timezone' => 'UTC',
                    'live_notification_alerts' => true,
                ],
            ],
            'sessionPolicy' => [
                'idleTimeoutMinutes' => (int) config('auth.session.idle_timeout'),
                'absoluteTimeoutMinutes' => (int) config('auth.session.absolute_timeout'),
            ],
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'preferences.timezone' => ['required', 'timezone'],
            'preferences.live_notification_alerts' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $oldValues = [
            'name' => $user->name,
            'preferences' => $user->preferences ?? [],
        ];
        $preferences = [
            'timezone' => $validated['preferences']['timezone'],
            'live_notification_alerts' => (bool) $validated['preferences']['live_notification_alerts'],
        ];

        DB::transaction(function () use ($user, $validated, $preferences, $oldValues, $auditLogger, $request): void {
            $user->update([
                'name' => $validated['name'],
                'preferences' => $preferences,
            ]);

            $auditLogger->record(
                'PROFILE_UPDATED',
                $user,
                $request->user(),
                $oldValues,
                ['name' => $validated['name'], 'preferences' => $preferences]
            );
        });

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Profile and preferences updated.');
    }
}
