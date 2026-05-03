<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ParametreController;
use App\Http\Controllers\Admin\FraisEcoleController;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // 📄 Routes Vues (Blade)
    Route::get('/parametres/cycles', [ParametreController::class, 'cycles'])->name('cycles');
    Route::get('/parametres/niveaux', [ParametreController::class, 'niveaux'])->name('niveaux');
    Route::get('/parametres/classes', [ParametreController::class, 'classes'])->name('classes');
    Route::get('/parametres/annees', [ParametreController::class, 'annees'])->name('annees');
    Route::get('/frais-ecoles', [FraisEcoleController::class, 'index'])->name('frais.index');

    // 🟢 Routes API JSON (Paramètres génériques)
    // Ex: GET /api/admin/parametres/cycle, POST /api/admin/parametres/niveau
    Route::prefix('api/parametres')->name('parametres.')->group(function () {
        Route::get('/{entity}', [ParametreController::class, 'index'])->name('index');
        Route::post('/{entity}', [ParametreController::class, 'store'])->name('store');
        Route::put('/{entity}/{id}', [ParametreController::class, 'update'])->name('update');
        Route::delete('/{entity}/{id}', [ParametreController::class, 'destroy'])->name('destroy');
    });

    // 🟢 Routes API JSON (Frais École - Spécifiques)
    Route::prefix('api/frais-ecoles')->name('frais.')->group(function () {
        Route::get('/', [FraisEcoleController::class, 'list'])->name('list');
        Route::post('/', [FraisEcoleController::class, 'store'])->name('store');
        Route::put('/{id}', [FraisEcoleController::class, 'update'])->name('update');
        Route::delete('/{id}', [FraisEcoleController::class, 'destroy'])->name('destroy');
        
        // Endpoints métier
        Route::get('/niveau/{niveauId}/annee/{anneeId}', [FraisEcoleController::class, 'getByNiveauAnnee'])->name('by-niveau-annee');
        Route::post('/clone', [FraisEcoleController::class, 'cloneAnnee'])->name('clone');
        Route::get('/grouped', [FraisEcoleController::class, 'grouped'])->name('grouped');
    });
});