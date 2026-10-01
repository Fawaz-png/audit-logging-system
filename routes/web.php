<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ActivateAccountController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FindingClosureController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\FindingEvidenceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Middleware\EncryptHistory;

// Public Routes
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/activate-account/{token}', [ActivateAccountController::class, 'create'])
        ->name('account.activate');

    Route::post('/activate-account', [ActivateAccountController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('account.activate.store');

    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    /* Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store'); */

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

// Authenticated Routes
Route::middleware(['auth', EncryptHistory::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])
        ->name('logout');

    Route::get('/security/two-factor', function () {
        return Inertia::render('Auth/TwoFactorAuthentication');
    })->name('two-factor.settings');

    Route::get('/password/change', [PasswordController::class, 'edit'])
        ->name('password.change');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::put('/password/change', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/roles', [RolePermissionController::class, 'index'])
        ->name('roles.index');

    Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'update'])
        ->name('roles.permissions.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/reports', [ReportsController::class, 'index'])
        ->name('reports.index');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/notifications/summary', [NotificationController::class, 'summary'])
        ->name('notifications.summary');

    Route::get('/notifications/summary', [NotificationController::class, 'summary'])
        ->name('notifications.summary');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index');

    Route::post('/notifications/{notification}/open', [NotificationController::class, 'open'])
        ->name('notifications.open');

    Route::get('/audits', [AuditController::class, 'index'])
        ->name('audits.index');

    Route::get('/audits/create', [AuditController::class, 'create'])
        ->name('audits.create');

    Route::post('/audits', [AuditController::class, 'store'])
        ->name('audits.store');

    Route::get('/audits/{audit}/edit', [AuditController::class, 'edit'])
        ->name('audits.edit');

    Route::put('/audits/{audit}', [AuditController::class, 'update'])
        ->name('audits.update');

    Route::get('/audits/{audit}', [AuditController::class, 'show'])
        ->name('audits.show');

    Route::get('/audits/{audit}/findings/create', [FindingController::class, 'create'])
        ->name('findings.create');

    Route::post('/audits/{audit}/findings', [FindingController::class, 'store'])
        ->name('findings.store');

    Route::get('/findings', [FindingController::class, 'index'])
        ->name('findings.index');

    Route::get('/findings/{finding}', [FindingController::class, 'show'])
        ->name('findings.show');

    Route::post('/findings/{finding}/closure-requests', [FindingClosureController::class, 'store'])
        ->name('findings.closure-requests.store');

    Route::post('/findings/{finding}/closure-requests/approve', [FindingClosureController::class, 'approve'])
        ->name('findings.closure-requests.approve');

    Route::post('/findings/{finding}/closure-requests/reject', [FindingClosureController::class, 'reject'])
        ->name('findings.closure-requests.reject');

    Route::get('/findings/{finding}/evidence/{evidence}', [FindingEvidenceController::class, 'download'])
        ->name('findings.evidences.download');

    Route::get('/findings/{finding}/edit', [FindingController::class, 'edit'])
        ->name('findings.edit');

    Route::put('/findings/{finding}', [FindingController::class, 'update'])
        ->name('findings.update');

    Route::delete('/findings/{finding}', [FindingController::class, 'destroy'])
        ->name('findings.destroy');

    Route::post(
        '/findings/{finding}/respond',
        [FindingController::class, 'respond']
    )->name('findings.respond');

    Route::post(
        '/findings/{finding}/comments',
        [FindingController::class, 'comment']
    )->name('findings.comments.store');

    Route::post(
        '/findings/{finding}/corrective-action',
        [FindingController::class, 'proposeAction']
    )->name('findings.actions.store');

    Route::post(
        '/finding-actions/{action}/approve',
        [FindingController::class, 'approveAction']
    )->name('finding-actions.approve');

    Route::post(
        '/finding-actions/{action}/reject',
        [FindingController::class, 'rejectAction']
    )->name('finding-actions.reject');

    Route::post(
        '/finding-actions/{action}/start',
        [FindingController::class, 'startRemediation']
    )->name('finding-actions.start');

    Route::post(
        '/finding-actions/{action}/complete',
        [FindingController::class, 'completeRemediation']
    )->name('finding-actions.complete');

});
