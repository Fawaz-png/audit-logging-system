<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by(
                strtolower($request->input('email')) . '|' . $request->ip()
            );
        });

        Event::listen(function (Login $event) {
            // Only initialize if the user object exists
            if ($event->user instanceof User) {
                $session = request()->session();

                $session->put('auth_session_version', (int) $event->user->session_version);
                $session->put('auth_session_started_at', now()->timestamp);
                $session->put('auth_session_last_activity_at', now()->timestamp);
            }
        });
    }
}
