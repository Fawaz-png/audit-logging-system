<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name']),

            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(
        StoreUserRequest $request,
        UserService $userService
    ): RedirectResponse {
        $userService->provision($request->validated(), $request->user());

        return redirect()
            ->route('users.create')
            ->with('success', 'User created successfully. An activation email has been sent.');
    }

    public function index(): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['role', 'department'])
            ->latest()
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
        ]);
    }
}
