<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Library\CharacterController;
use App\Http\Controllers\Library\Character\OutfitController;
use App\Http\Controllers\Library\LocationController;
use App\Http\Controllers\Library\StyleController;
use App\Http\Controllers\Library\PropController;
use App\Http\Controllers\Library\VirtualTalentController;
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

    Route::prefix('library/virtual-talents')->name('library.virtual-talents.')->group(function (): void {
        Route::get('/', [VirtualTalentController::class, 'index'])->name('index');
        Route::get('/{character}', [VirtualTalentController::class, 'show'])->name('show');
        Route::get('/{character}/edit', [VirtualTalentController::class, 'edit'])->name('edit');
        Route::put('/{character}', [VirtualTalentController::class, 'update'])->name('update');
        Route::post('/{character}/generate-bio', [VirtualTalentController::class, 'generateBio'])->name('generate-bio');
    });
});

require __DIR__.'/auth.php';
