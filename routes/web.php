<?php

use App\Http\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('incidents', IncidentController::class)->only(['index', 'store']);
    Route::patch('incidents/{incident}/status', [IncidentController::class, 'updateStatus'])
        ->name('incidents.status.update');
});

require __DIR__.'/settings.php';
