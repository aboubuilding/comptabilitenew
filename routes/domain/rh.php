<?php

use App\Http\Controllers\Domain\Rh\DashboardPaieController;
use App\Http\Controllers\Domain\Rh\EmployeController;
use App\Http\Controllers\Domain\Rh\PaieController;
use App\Http\Controllers\Domain\Rh\PeriodeController;
use App\Http\Controllers\Domain\Rh\ProfessionController;
use App\Http\Controllers\Domain\Rh\RemunerationController;
use App\Http\Controllers\Domain\Rh\AssureController;
use App\Http\Controllers\Domain\Rh\AsuranceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Ressources Humaines — préfixe /rh, noms rh.*
|--------------------------------------------------------------------------
| Structure :
|   rh.employes.*       → 5.1 Employés
|   rh.professions.*    → 5.1 Professions
|   rh.periodes.*       → 5.2 Périodes de paie
|   rh.paies.*          → 5.2 Bulletins + dashboard + exports PDF
|   rh.remunerations.*  → 5.2 Rémunérations / déductions / retenues
|
| Convention d'ordre : les routes littérales (/export, /dashboard,
| /periodes/{id}/pdf, /{id}/pdf) sont TOUJOURS déclarées AVANT les
| routes paramétrées (/{id}) — sinon Laravel les prendrait pour un ID.
*/

Route::prefix('rh')->name('rh.')->group(function () {

    /* ==========================================================
     | 5.1 EMPLOYÉS
     |========================================================== */
    Route::prefix('employes')->name('employes.')->group(function () {
        Route::get('/',              [EmployeController::class, 'index'])   ->name('index');
        Route::get('/export',        [EmployeController::class, 'export'])  ->name('export');
        Route::post('/',             [EmployeController::class, 'store'])   ->name('store');
        Route::get('/{employe}',     [EmployeController::class, 'show'])    ->name('show');
        Route::put('/{employe}',     [EmployeController::class, 'update'])  ->name('update');
        Route::delete('/{employe}',  [EmployeController::class, 'destroy']) ->name('destroy');
    });

    /* ==========================================================
     | 5.1 PROFESSIONS
     |========================================================== */
    Route::prefix('professions')->name('professions.')->group(function () {
        Route::get('/',                 [ProfessionController::class, 'index'])   ->name('index');
        Route::post('/',                [ProfessionController::class, 'store'])   ->name('store');
        Route::put('/{profession}',     [ProfessionController::class, 'update'])  ->name('update');
        Route::delete('/{profession}',  [ProfessionController::class, 'destroy']) ->name('destroy');
    });

    /* ==========================================================
     | 5.2 PÉRIODES DE PAIE
     |========================================================== */
    Route::prefix('periodes')->name('periodes.')->group(function () {
        Route::get('/',                     [PeriodeController::class, 'index'])            ->name('index');
        Route::post('/',                    [PeriodeController::class, 'store'])            ->name('store');
        Route::put('/{periode}',            [PeriodeController::class, 'update'])           ->name('update');
        Route::delete('/{periode}',         [PeriodeController::class, 'destroy'])          ->name('destroy');

        // Génération en masse (dispatch job queue)
        Route::post('/{periode}/generer',   [PeriodeController::class, 'genererBulletins']) ->name('genererBulletins');
        // Polling du statut de génération
        Route::get('/{periode}/statut-generation', [PeriodeController::class, 'statutGeneration'])->name('statutGeneration');
    });

    /* ==========================================================
     | 5.2 BULLETINS DE PAIE
     |========================================================== */
    Route::prefix('paies')->name('paies.')->group(function () {

        // --- Routes littérales AVANT /{paie} ---
        Route::get('/',                    [PaieController::class, 'index'])         ->name('index');
        Route::get('/export',              [PaieController::class, 'export'])        ->name('export');
        Route::get('/dashboard',           [DashboardPaieController::class, 'index'])->name('dashboard');
        Route::post('/',                   [PaieController::class, 'store'])         ->name('store');

        // Export PDF groupé pour une période (littéral car 'periodes' ≠ ID)
        Route::get('/periodes/{periode}/pdf', [PaieController::class, 'exportPeriodePdf'])->name('exportPeriodePdf');

        // Export PDF d'un bulletin individuel (littéral 'pdf' avant /{paie})
        Route::get('/{paie}/pdf',          [PaieController::class, 'exportPdf'])     ->name('exportPdf');

        // Actions sur un bulletin
        Route::post('/{paie}/valider',     [PaieController::class, 'valider'])       ->name('valider');
        Route::post('/{paie}/payer',       [PaieController::class, 'payer'])         ->name('payer');
        Route::post('/{paie}/annuler',     [PaieController::class, 'annuler'])       ->name('annuler');
        Route::post('/{paie}/retrait',     [PaieController::class, 'marquerRetrait'])->name('retrait');

        // Route paramétrée EN DERNIER
        Route::get('/{paie}',              [PaieController::class, 'show'])          ->name('show');
    });

    /* ==========================================================
     | 5.2 RÉMUNÉRATIONS / DÉDUCTIONS / RETENUES
     |========================================================== */
    Route::prefix('remunerations')->name('remunerations.')->group(function () {
        Route::get('/',                [RemunerationController::class, 'index'])            ->name('index');
        Route::post('/remunerations',  [RemunerationController::class, 'storeRemuneration'])->name('storeRemuneration');
        Route::post('/deductions',     [RemunerationController::class, 'storeDeduction'])   ->name('storeDeduction');
        Route::post('/retenues',       [RemunerationController::class, 'storeRetenue'])     ->name('storeRetenue');
    });

     // 5.3 Assurances du personnel
   Route::prefix('assures')->name('assures.')->group(function () {
        // ⚠️ /export AVANT /{assure}
        Route::get('/export', [AssureController::class, 'export'])->name('export');
        Route::get('/', [AssureController::class, 'index'])->name('index');
        Route::get('/create', [AssureController::class, 'create'])->name('create');
        Route::post('/', [AssureController::class, 'store'])->name('store');
        Route::get('/{assure}/edit', [AssureController::class, 'edit'])->name('edit');
        Route::put('/{assure}', [AssureController::class, 'update'])->name('update');
        Route::delete('/{assure}', [AssureController::class, 'destroy'])->name('destroy');
    });


     // 5.3.a Catalogue des assurances (assureurs / polices)
    Route::prefix('asurances')->name('asurances.')->group(function () {
        // ⚠️ /export AVANT /{asurance}
        Route::get('/export', [AsuranceController::class, 'export'])->name('export');
        Route::get('/', [AsuranceController::class, 'index'])->name('index');
        Route::get('/create', [AsuranceController::class, 'create'])->name('create');
        Route::post('/', [AsuranceController::class, 'store'])->name('store');
        Route::get('/{asurance}/edit', [AsuranceController::class, 'edit'])->name('edit');
        Route::put('/{asurance}', [AsuranceController::class, 'update'])->name('update');
        Route::delete('/{asurance}', [AsuranceController::class, 'destroy'])->name('destroy');
    });
});