<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        Auth::logoutOtherDevices($validated['password']);
        $user->increment('session_version');
        $user->refresh();

        $request->session()->regenerate();
        $request->session()->put('auth_session_version', (int) $user->session_version);
        $auditLogger->record('USER_PASSWORD_CHANGED', $user, $user, null, [
            'session_version' => (int) $user->session_version,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Your password has been changed successfully.');
    }
}
