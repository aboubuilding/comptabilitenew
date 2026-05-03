<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Caisse\DepenseController;
use App\Http\Controllers\Caisse\DecaissementController;
use App\Http\Controllers\Caisse\CaisseController;

// ─────────────────────────────────────────────────────────────
// 💰 DÉPENSES
// ─────────────────────────────────────────────────────────────
Route::prefix('depenses')->name('depenses.')->group(function () {
    Route::get('/',               [DepenseController::class, 'index'])->name('index');   // 📄 Vue Blade
    Route::post('/',              [DepenseController::class, 'store'])->name('store');   // 🟢 JSON
    Route::get('/{id}',           [DepenseController::class, 'show'])->name('show');     // 🟢 JSON
    Route::put('/{id}/valider',   [DepenseController::class, 'valider'])->name('valider'); // 🟢 JSON
    Route::put('/{id}/rejeter',   [DepenseController::class, 'rejeter'])->name('rejeter'); // 🟢 JSON
    Route::delete('/{id}',        [DepenseController::class, 'destroy'])->name('destroy'); // 🟢 JSON
});

// ─────────────────────────────────────────────────────────────
// 📉 DÉCAISSEMENTS
// ─────────────────────────────────────────────────────────────
Route::prefix('decaissements')->name('decaissements.')->group(function () {
    Route::get('/',               [DecaissementController::class, 'index'])->name('index');   // 📄 Vue Blade
    Route::get('/stats',          [DecaissementController::class, 'stats'])->name('stats');   // 🟢 JSON
    Route::get('/{id}',           [DecaissementController::class, 'show'])->name('show');     // 🟢 JSON
});


// 🏦 CAISSES
Route::prefix('caisses')->name('caisses.')->group(function () {
    // 📄 Vues Blade
    Route::get('/',               [CaisseController::class, 'index'])->name('index');
    Route::get('/{id}',           [CaisseController::class, 'show'])->name('show'); // ✅ Retourne une Vue

    // 🟢 Routes JSON (API / AJAX / Mobile)
    Route::post('/',                    [CaisseController::class, 'store'])->name('store');
    Route::get('/{id}/solde',           [CaisseController::class, 'solde'])->name('solde');
    Route::post('/{id}/ouvrir',         [CaisseController::class, 'ouvrir'])->name('ouvrir');
    Route::post('/{id}/cloturer',       [CaisseController::class, 'cloturer'])->name('cloturer');
    Route::get('/reporting/ecarts',     [CaisseController::class, 'ecartReport'])->name('reporting.ecarts');
});

// 👥 CAISSIERS
Route::prefix('caissiers')->name('caissiers.')->group(function () {
    Route::get('/',                     [CaisseController::class, 'caissiers'])->name('index');
    Route::post('/',                    [CaisseController::class, 'storeCaissier'])->name('store');
    Route::delete('/{id}',              [CaisseController::class, 'destroyCaissier'])->name('destroy');
    Route::get('/{id}/mouvements',      [CaisseController::class, 'caissierMouvements'])->name('mouvements');
});