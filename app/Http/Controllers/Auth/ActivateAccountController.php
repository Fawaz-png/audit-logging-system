<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ActivateAccountController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ActivateAccount', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function store(ActivateAccountRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'active' => true,
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been activated. You can now sign in.');
    }
}
