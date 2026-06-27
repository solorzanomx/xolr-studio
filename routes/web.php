<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Library\CharacterController;
use App\Http\Controllers\Library\Character\OutfitController;
use App\Http\Controllers\Library\LocationController;
use App\Http\Controllers\Library\StyleController;
use App\Http\Controllers\Library\PropController;
use App\Http\Controllers\Studio\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/settings', SettingsController::class)->name('settings');

    Route::prefix('library')->name('library.')->group(function (): void {
        Route::resource('characters', CharacterController::class);
        Route::post('characters/{character}/outfits', [OutfitController::class, 'store'])->name('characters.outfits.store');
        Route::put('outfits/{outfit}', [OutfitController::class, 'update'])->name('outfits.update');
        Route::delete('outfits/{outfit}', [OutfitController::class, 'destroy'])->name('outfits.destroy');
        Route::resource('locations', LocationController::class);
        Route::get('styles', StyleController::class)->name('styles.index');
        Route::resource('props', PropController::class);
    });
});

require __DIR__.'/auth.php';
