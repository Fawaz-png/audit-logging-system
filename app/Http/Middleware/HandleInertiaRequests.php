<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request) ?? 'dev';
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()
                    ? [
                        'uuid' => $request->user()->uuid,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role?->slug,
                        'department' => $request->user()->department?->name,
                        'preferences' => $request->user()->preferences ?? [
                            'timezone' => 'UTC',
                            'live_notification_alerts' => true,
                        ],
                        'two_factor_enabled' => $request->user()?->hasEnabledTwoFactorAuthentication() ?? false,

                        'permissions' => $request->user()
                            ->role?->permissions
                            ->pluck('slug')
                            ->values()
                            ->all() ?? [],
                    ]
                    : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => $request->user()
                ? [
                    'unread_count' => $request->user()->unreadNotifications()->count(),
                    'recent' => $request->user()
                        ->notifications()
                        ->latest()
                        ->limit(5)
                        ->get(['id', 'data', 'read_at', 'created_at']),
                ]
                : ['unread_count' => 0, 'recent' => []],
        ];
    }
}
