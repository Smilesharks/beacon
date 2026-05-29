<?php

use Arielcerda\Beacon\Http\Controllers\CollectionRulesController;
use Arielcerda\Beacon\Http\Controllers\DashboardController;
use Arielcerda\Beacon\Http\Controllers\NotificationsController;
use Arielcerda\Beacon\Http\Controllers\SettingsController;
use Arielcerda\Beacon\Http\Controllers\SitewideController;
use Illuminate\Support\Facades\Route;

Route::prefix('cp/beacon')
    ->name('beacon.')
    ->middleware(['statamic.cp', 'auth'])
    ->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Global settings
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Collection rules
        Route::get('/collections', [CollectionRulesController::class, 'index'])->name('collections.index');
        Route::post('/collections', [CollectionRulesController::class, 'store'])->name('collections.store');
        Route::put('/collections/{id}', [CollectionRulesController::class, 'update'])->name('collections.update');
        Route::delete('/collections/{id}', [CollectionRulesController::class, 'destroy'])->name('collections.destroy');

        // Sitewide notification
        Route::get('/sitewide', [SitewideController::class, 'index'])->name('sitewide.index');
        Route::post('/sitewide', [SitewideController::class, 'update'])->name('sitewide.update');

        // Notifications API (used by Vue fieldtype)
        Route::post('/notifications', [NotificationsController::class, 'store'])->name('notifications.store');
        Route::put('/notifications/{id}', [NotificationsController::class, 'update'])->name('notifications.update');
        Route::delete('/notifications/{id}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');
    });
