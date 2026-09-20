<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use Inertia\Inertia;

// Public Routes
Route::get('/hello', function () {
    return Inertia::render('Hello');
});

Route::get('/', function () {
    return view('welcome');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/logout', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');

    Route::get('/audits/create', [AuditController::class, 'create'])
        ->name('audits.create');

    Route::post('/audits', [AuditController::class, 'store'])
        ->name('audits.store');

    Route::get('/audits/{audit}', [AuditController::class, 'show'])
        ->name('audits.show');

    // Secure the audit logging endpoint
    Route::post('/audits', [AuditController::class, 'store'])->name('audits.store');
});