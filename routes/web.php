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
use App\Http\Controllers\Production\PropertyController;
use App\Http\Controllers\Production\CampaignController;
use App\Http\Controllers\Production\RenderController;
use App\Http\Controllers\ContentMachine\VideoConceptController;
use App\Http\Controllers\ContentMachine\VideoSeriesController;
use App\Http\Controllers\ContentMachine\VideoHookController;
use App\Http\Controllers\Audio\AudioAssetController;
use App\Http\Controllers\Audio\VoiceProfileController;
use App\Http\Controllers\Production\TalkingRenderController;
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
    Route::get('shots', fn() => redirect('/projects'))->name('shots.index');
    Route::post('scenes/{scene}/shots', [ShotController::class, 'store'])->name('scenes.shots.store');
    Route::get('shots/{shot}', [ShotController::class, 'show'])->name('shots.show');
    Route::put('shots/{shot}', [ShotController::class, 'update'])->name('shots.update');
    Route::delete('shots/{shot}', [ShotController::class, 'destroy'])->name('shots.destroy');
    Route::post('shots/{shot}/compose-prompt', [ShotController::class, 'composePrompt'])->name('shots.compose-prompt');
    Route::post('shots/{shot}/save-prompt', [ShotController::class, 'savePrompt'])->name('shots.save-prompt');
    Route::post('shots/{shot}/characters', [ShotController::class, 'addCharacter'])->name('shots.characters.store');
    Route::delete('shots/{shot}/characters/{character}', [ShotController::class, 'removeCharacter'])->name('shots.characters.destroy');

    // Renders
    Route::post('shots/{shot}/renders', [RenderController::class, 'store'])->name('shots.renders.store');
    Route::post('renders/{render}/approve', [RenderController::class, 'approve'])->name('renders.approve');
    Route::delete('renders/{render}', [RenderController::class, 'destroy'])->name('renders.destroy');

    // Talking renders (Lip Sync)
    Route::post('shots/{shot}/talking-renders', [TalkingRenderController::class, 'store'])->name('shots.talking-renders.store');
    Route::post('talking-renders/{talkingRender}/approve', [TalkingRenderController::class, 'approve'])->name('talking-renders.approve');
    Route::delete('talking-renders/{talkingRender}', [TalkingRenderController::class, 'destroy'])->name('talking-renders.destroy');
    Route::post('talking-renders/estimate-cost', [TalkingRenderController::class, 'estimateCost'])->name('talking-renders.estimate-cost');

    // Properties (nested bajo proyecto)
    Route::post('projects/{project}/properties', [PropertyController::class, 'store'])->name('projects.properties.store');
    Route::get('properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::delete('properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/generate-shots', [CampaignController::class, 'generateShots'])->name('campaigns.generate-shots');

    // Content Machine — Ideas Bank, Video Concepts, Series, Hooks
    Route::prefix('content-machine')->name('content-machine.')->group(function (): void {
        Route::get('/', [VideoConceptController::class, 'index'])->name('index');
        Route::get('/concepts/create', [VideoConceptController::class, 'create'])->name('concepts.create');
        Route::post('/concepts', [VideoConceptController::class, 'store'])->name('concepts.store');
        Route::get('/concepts/{concept}', [VideoConceptController::class, 'show'])->name('concepts.show');
        Route::get('/concepts/{concept}/edit', [VideoConceptController::class, 'edit'])->name('concepts.edit');
        Route::put('/concepts/{concept}', [VideoConceptController::class, 'update'])->name('concepts.update');
        Route::delete('/concepts/{concept}', [VideoConceptController::class, 'destroy'])->name('concepts.destroy');
        Route::post('/concepts/{concept}/generate', [VideoConceptController::class, 'generate'])->name('concepts.generate');
        Route::post('/concepts/{concept}/thumbnail-shots', [VideoConceptController::class, 'addThumbnailShot'])->name('concepts.thumbnail-shots.store');

        Route::post('/series', [VideoSeriesController::class, 'store'])->name('series.store');
        Route::put('/series/{series}', [VideoSeriesController::class, 'update'])->name('series.update');
        Route::delete('/series/{series}', [VideoSeriesController::class, 'destroy'])->name('series.destroy');

        Route::get('/hooks', [VideoHookController::class, 'index'])->name('hooks.index');
        Route::post('/hooks', [VideoHookController::class, 'store'])->name('hooks.store');
        Route::put('/hooks/{hook}', [VideoHookController::class, 'update'])->name('hooks.update');
        Route::delete('/hooks/{hook}', [VideoHookController::class, 'destroy'])->name('hooks.destroy');
    });

    // Audio Studio
    Route::prefix('audio')->name('audio.')->group(function (): void {
        Route::get('/', [AudioAssetController::class, 'index'])->name('index');
        Route::post('/assets', [AudioAssetController::class, 'store'])->name('assets.store');
        Route::delete('/assets/{audioAsset}', [AudioAssetController::class, 'destroy'])->name('assets.destroy');
        Route::post('/assets/{audioAsset}/subtitles', [AudioAssetController::class, 'generateSubtitles'])->name('assets.subtitles');

        Route::post('/characters/{character}/voice-profiles', [VoiceProfileController::class, 'store'])->name('voice-profiles.store');
        Route::put('/voice-profiles/{voiceProfile}', [VoiceProfileController::class, 'update'])->name('voice-profiles.update');
        Route::delete('/voice-profiles/{voiceProfile}', [VoiceProfileController::class, 'destroy'])->name('voice-profiles.destroy');
    });
});

require __DIR__.'/auth.php';
