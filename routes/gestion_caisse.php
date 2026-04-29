<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepenseController;
use App\Http\Controllers\DecaissementController;
use App\Http\Controllers\CaisseController;

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

// ─────────────────────────────────────────────────────────────
// 🏦 CAISSES
// ─────────────────────────────────────────────────────────────
Route::prefix('caisses')->name('caisses.')->group(function () {
    Route::get('/',               [CaisseController::class, 'index'])->name('index');         // 📄 Vue Blade
    Route::get('/{id}',           [CaisseController::class, 'show'])->name('show');           // 🟢 JSON
    Route::get('/{id}/solde',     [CaisseController::class, 'solde'])->name('solde');         // 🟢 JSON
    Route::post('/{id}/ouvrir',   [CaisseController::class, 'ouvrir'])->name('ouvrir');       // 🟢 JSON
    Route::post('/{id}/cloturer', [CaisseController::class, 'cloturer'])->name('cloturer');   // 🟢 JSON
    Route::delete('/{id}',        [CaisseController::class, 'destroy'])->name('destroy');     // 🟢 JSON ✅ AJOUTÉ

    });