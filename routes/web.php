<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/hello', function () {
    return Inertia::render('Hello');
});

Route::get('/', function () {
    return view('welcome');
});

Route::post('/audits', [AuditController::class, 'store'])
    ->name('audits.store');

