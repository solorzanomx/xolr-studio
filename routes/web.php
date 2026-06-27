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
use App\Http\Controllers\Production\ProjectController;
use App\Http\Controllers\Production\SeasonController;
use App\Http\Controllers\Production\EpisodeController;
use App\Http\Controllers\Production\SceneController;
use App\Http\Controllers\Production\ShotController;
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

    // Projects
    Route::resource('projects', ProjectController::class);

    // Seasons (shallow — no dedicated index page, lives in Project show)
    Route::post('projects/{project}/seasons', [SeasonController::class, 'store'])->name('projects.seasons.store');
    Route::get('projects/{project}/seasons/{season}', [SeasonController::class, 'show'])->name('projects.seasons.show');
    Route::put('seasons/{season}', [SeasonController::class, 'update'])->name('seasons.update');
    Route::delete('seasons/{season}', [SeasonController::class, 'destroy'])->name('seasons.destroy');

    // Episodes
    Route::post('seasons/{season}/episodes', [EpisodeController::class, 'store'])->name('seasons.episodes.store');
    Route::get('episodes/{episode}', [EpisodeController::class, 'show'])->name('episodes.show');
    Route::get('episodes/{episode}/edit', [EpisodeController::class, 'edit'])->name('episodes.edit');
    Route::put('episodes/{episode}', [EpisodeController::class, 'update'])->name('episodes.update');
    Route::put('episodes/{episode}/script', [EpisodeController::class, 'updateScript'])->name('episodes.script');
    Route::delete('episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');

    // Scenes
    Route::post('episodes/{episode}/scenes', [SceneController::class, 'store'])->name('episodes.scenes.store');
    Route::put('scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
    Route::delete('scenes/{scene}', [SceneController::class, 'destroy'])->name('scenes.destroy');

    // Shots
    Route::post('scenes/{scene}/shots', [ShotController::class, 'store'])->name('scenes.shots.store');
    Route::put('shots/{shot}', [ShotController::class, 'update'])->name('shots.update');
    Route::delete('shots/{shot}', [ShotController::class, 'destroy'])->name('shots.destroy');
});

require __DIR__.'/auth.php';
