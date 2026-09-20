<?php

use App\Http\Controllers\Admin\Finances\EncaissementController;
use App\Http\Controllers\Admin\Finances\FraisEcoleController;
use App\Http\Controllers\Admin\Finances\PaiementController;
use App\Http\Controllers\Admin\Finances\PlanEcheancierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Finances — préfixe /finances, noms finances.*
|--------------------------------------------------------------------------
| Premier fichier de ce domaine — à compléter au fur et à mesure
| (paiements, caisses, dépenses...).
*/

Route::prefix('finances')->name('finances.')->group(function () {

    // Frais scolaires — ajout/modification via modale (même pattern que Cycle)
    Route::get('/frais-ecoles', [FraisEcoleController::class, 'index'])->name('frais-ecoles.index');
    Route::get('/frais-ecoles/{frais_ecole}/edit', [FraisEcoleController::class, 'edit'])->name('frais-ecoles.edit');
    Route::post('/frais-ecoles', [FraisEcoleController::class, 'store'])->name('frais-ecoles.store');
    Route::put('/frais-ecoles/{frais_ecole}', [FraisEcoleController::class, 'update'])->name('frais-ecoles.update');
    Route::post('/frais-ecoles/{frais_ecole}/archiver', [FraisEcoleController::class, 'archiver'])->name('frais-ecoles.archiver');

    // Plans d'échéancier — CRUD minimal (nom/description) ; la gestion
    // des lignes est pour un prochain tour.
    Route::get('/plan-echeanciers', [PlanEcheancierController::class, 'index'])->name('plan-echeanciers.index');
    Route::get('/plan-echeanciers/{plan_echeancier}/edit', [PlanEcheancierController::class, 'edit'])->name('plan-echeanciers.edit');
    Route::post('/plan-echeanciers', [PlanEcheancierController::class, 'store'])->name('plan-echeanciers.store');
    Route::put('/plan-echeanciers/{plan_echeancier}', [PlanEcheancierController::class, 'update'])->name('plan-echeanciers.update');
    Route::post('/plan-echeanciers/{plan_echeancier}/archiver', [PlanEcheancierController::class, 'archiver'])->name('plan-echeanciers.archiver');

    // Paiements — liste globale (index) + création "par élève" (recherche
    // puis écran à 3 zones). Ordre important : les routes littérales
    // (nouveau, recherche-panier) doivent être déclarées AVANT
    // /paiements/{eleve}, sinon Laravel les prendrait pour un id d'élève.
    Route::get('/paiements', [PaiementController::class, 'index'])->name('paiements.index');
    Route::get('/paiements/nouveau', [PaiementController::class, 'rechercheEleve'])->name('paiements.recherche');
    Route::get('/paiements/recherche-panier', [PaiementController::class, 'rechercherPanier'])->name('paiements.recherche-panier');
    Route::get('/paiements/{eleve}', [PaiementController::class, 'show'])->name('paiements.show');
    Route::post('/paiements', [PaiementController::class, 'store'])->name('paiements.store');

    // Encaissements — Caissier seul (cahier des charges §4, 2.3)
    Route::get('/encaissements', [EncaissementController::class, 'index'])->name('encaissements.index');
    Route::post('/encaissements/{detail}/encaisser', [EncaissementController::class, 'encaisser'])->name('encaissements.encaisser');
    Route::post('/encaissements/{detail}/annuler', [EncaissementController::class, 'annuler'])->name('encaissements.annuler');


     Route::get('/',              [BanqueController::class, 'index'])   ->name('index');
            Route::post('/',             [BanqueController::class, 'store'])   ->name('store');
            Route::put('/{banque}',      [BanqueController::class, 'update'])  ->name('update');
            Route::delete('/{banque}',   [BanqueController::class, 'destroy']) ->name('destroy');

            Route::get('/{banque}/export-pdf', [BanqueController::class, 'exportPdf'])->name('exportPdf');

            Route::get('/',        [RapprochementController::class, 'index'])    ->name('index');
    Route::post('/analyser', [RapprochementController::class, 'analyser'])->name('analyser');
    Route::post('/appliquer', [RapprochementController::class, 'appliquer'])->name('appliquer');
    
      // Liste des cheques 
Route::get('/',                     [ChequeController::class, 'index'])     ->name('index');
            Route::post('/',                    [ChequeController::class, 'store'])     ->name('store');
            Route::post('/{cheque}/rapprocher', [ChequeController::class, 'rapprocher'])->name('rapprocher');
            Route::get('/export',               [ChequeController::class, 'export'])    ->name('export');
       
       
       
            });

  