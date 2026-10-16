<?php

use App\Http\Controllers\IncidentCommentController;
use App\Http\Controllers\IncidentController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', fn (): RedirectResponse => to_route('incidents.index'))->name('dashboard');
    Route::resource('incidents', IncidentController::class)->only(['index', 'store', 'show']);
    Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store'])
        ->name('incidents.comments.store');
    Route::patch('incidents/{incident}/status', [IncidentController::class, 'updateStatus'])
        ->name('incidents.status.update');
});

require __DIR__.'/settings.php';
