<?php

use App\Http\Controllers\Domain\Finances\EncaissementController;
use App\Http\Controllers\Domain\Finances\FraisEcoleController;
use App\Http\Controllers\Domain\Finances\PaiementController;
use App\Http\Controllers\Domain\Finances\PlanEcheancierController;
use App\Http\Controllers\Domain\Finances\BanqueController;
use App\Http\Controllers\Domain\Finances\CaisseController;
use App\Http\Controllers\Domain\Finances\ChequeController;
use App\Http\Controllers\Domain\Finances\DepenseController;
use App\Http\Controllers\Domain\Finances\RapprochementController;
use App\Http\Controllers\Domain\Finances\CentreAnalytiqueController;
use App\Http\Controllers\Domain\Finances\RapportAnalytiqueController;
use App\Http\Controllers\Domain\Finances\PrevisionController;
use App\Http\Controllers\Domain\Finances\RecouvrementController;
use App\Http\Controllers\Domain\Finances\RegleVentilationController;
use App\Http\Controllers\Domain\Finances\SouscriptionController;
use App\Http\Controllers\Domain\Finances\TrancheController;
use App\Http\Controllers\Domain\Finances\VentilationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Finances — préfixe /finances, noms finances.*
|--------------------------------------------------------------------------
| Un seul fichier de routes pour le domaine Finances. Toutes les routes
| sont ici protégées par le middleware 'auth' + ResolveAnneeScolaire
| (voir web.php qui require ce fichier DANS le groupe protégé).
|
| Convention de nommage :
|   finances.{sous-menu}.{action}     ex. finances.caisses.show
|   finances.{sous-menu}.{action}     ex. finances.depenses.valider
|
| Ordre des routes : les routes littérales (/export, /nouveau) doivent
| toujours être déclarées AVANT les routes paramétrées (/{id}) dans le
| même groupe, sinon Laravel confond le segment littéral avec un ID.
*/

Route::prefix('finances')->name('finances.')->group(function () {

   /* ---------- 2.1 Frais scolaires ---------- */
Route::prefix('frais-ecoles')->name('frais-ecoles.')->group(function () {
    Route::get('/',                [FraisEcoleController::class, 'index'])  ->name('index');
    Route::post('/',               [FraisEcoleController::class, 'store'])  ->name('store');
    Route::put('/{frais}',         [FraisEcoleController::class, 'update']) ->name('update');
    Route::delete('/{frais}',      [FraisEcoleController::class, 'destroy'])->name('destroy');
    Route::get('/export',          [FraisEcoleController::class, 'export']) ->name('export');
    // /export-pdf est déclaré AVANT /{frais}, sinon il est pris pour un identifiant.
    Route::get('/export-pdf', [FraisEcoleController::class, 'exportPdf'])->name('exportPdf');
    Route::get('/{frais}/edit', [FraisEcoleController::class, 'edit'])->name('edit')->whereNumber('frais');
    Route::get('/{frais}', [FraisEcoleController::class, 'show'])->name('show')->whereNumber('frais');
    
    
    });

/* ---------- 2.1 Plans d'échéancier ---------- */
Route::prefix('plan-echeanciers')->name('plan-echeanciers.')->group(function () {
    Route::get('/',                [PlanEcheancierController::class, 'index'])  ->name('index');
    Route::post('/',               [PlanEcheancierController::class, 'store'])  ->name('store');
    Route::get('/{plan}',          [PlanEcheancierController::class, 'show'])   ->name('show');
    Route::put('/{plan}',          [PlanEcheancierController::class, 'update']) ->name('update');
    Route::delete('/{plan}',       [PlanEcheancierController::class, 'destroy'])->name('destroy');
});

/* ---------- 2.1 Souscriptions ---------- */
Route::prefix('souscriptions')->name('souscriptions.')->group(function () {
    Route::get('/',                    [SouscriptionController::class, 'index'])  ->name('index');
    Route::post('/',                   [SouscriptionController::class, 'store'])  ->name('store');
    Route::put('/{souscription}',      [SouscriptionController::class, 'update']) ->name('update');
    Route::delete('/{souscription}',   [SouscriptionController::class, 'destroy'])->name('destroy');
    Route::get('/export',              [SouscriptionController::class, 'export']) ->name('export');
});

Route::prefix('tranches')->name('tranches.')->group(function () {
    Route::get('/',              [TrancheController::class, 'index'])  ->name('index');
    Route::post('/',             [TrancheController::class, 'store'])  ->name('store');
    Route::put('/{tranche}',     [TrancheController::class, 'update']) ->name('update');
    Route::delete('/{tranche}',  [TrancheController::class, 'destroy'])->name('destroy');
});
    /* ==========================================================
     | 2.2 — Paiements (saisie par le Comptable)
     |========================================================== */
    //
    // Ordre important : /nouveau et /recherche-panier sont littéraux et
    // doivent précéder /{eleve} — sinon Laravel les prendrait pour un ID.
    //
    // routes/domain/finances.php — dans le groupe finances.

Route::prefix('paiements')->name('paiements.')->group(function () {
    Route::get('/',                      [PaiementController::class, 'index'])          ->name('index');
    Route::get('/nouveau',               [PaiementController::class, 'rechercheEleve']) ->name('recherche');
    Route::get('/eleves',                [PaiementController::class, 'eleves'])         ->name('eleves');
    Route::get('/recherche-panier',      [PaiementController::class, 'rechercherPanier'])->name('recherche-panier');
    Route::get('/export',                [PaiementController::class, 'export'])         ->name('export');
    Route::post('/',                     [PaiementController::class, 'store'])          ->name('store');
    Route::get('/{eleve}',               [PaiementController::class, 'show'])           ->name('show')->whereNumber('eleve');
    Route::get('/{paiement}/detail',     [PaiementController::class, 'detail'])         ->name('detail')->whereNumber('paiement');
    Route::put('/{paiement}',            [PaiementController::class, 'update'])         ->name('update');
    Route::post('/{paiement}/annuler',   [PaiementController::class, 'annuler'])        ->name('annuler');
});

    /* ==========================================================
     | 2.3 — Encaissements (Caissier seul)
     |========================================================== */
    // routes/domain/finances.php — dans le groupe finances.


Route::prefix('encaissements')->name('encaissements.')->group(function () {
    Route::get('/',                                       [EncaissementController::class, 'index'])               ->name('index');
    Route::get('/nouveau',                                [EncaissementController::class, 'nouveau'])             ->name('nouveau');
    Route::get('/paiements-en-attente',                   [EncaissementController::class, 'paiementsEnAttente'])  ->name('paiements-en-attente');
    Route::get('/paiement/{paiement}/resume',             [EncaissementController::class, 'resume'])              ->name('paiement.resume');
    Route::post('/paiement/{paiement}/encaisser',         [EncaissementController::class, 'encaisserPaiement'])   ->name('paiement.encaisser');
    Route::post('/paiement/{paiement}/annuler-encaissement', [EncaissementController::class, 'annulerEncaissement']) ->name('paiement.annuler-encaissement');
    Route::post('/paiement/{paiement}/annuler',           [EncaissementController::class, 'annulerPaiement'])     ->name('paiement.annuler');
    Route::get('/paiement/{paiement}/recu',               [EncaissementController::class, 'recuPaiement'])        ->name('paiement.recu');
    Route::get('/{detail}/recu',                          [EncaissementController::class, 'recuPdf'])             ->name('recu');
});
    /* ==========================================================
     | 2.4 — Caisses (ouverture / clôture / journal)
     |========================================================== */
    Route::prefix('caisses')->name('caisses.')->group(function () {
        Route::get('/',                     [CaisseController::class, 'index'])         ->name('index');
        Route::post('/',                    [CaisseController::class, 'store'])         ->name('store');
        // Validation des écarts par la Direction — déclaré AVANT /{caisse}
        Route::get('/ecarts',               [\App\Http\Controllers\Domain\Finances\EcartCaisseController::class, 'index'])  ->name('ecarts.index');
        // Export de la liste des caisses — déclaré AVANT /{caisse}
        Route::get('/export',               [CaisseController::class, 'exportListe'])   ->name('exportListe');
        Route::post('/{caisse}/ecart',      [\App\Http\Controllers\Domain\Finances\EcartCaisseController::class, 'statuer'])->name('ecarts.statuer');
        Route::get('/{caisse}',             [CaisseController::class, 'show'])          ->name('show')->whereNumber('caisse');
        Route::post('/{caisse}/cloturer',   [CaisseController::class, 'cloturer'])      ->name('cloturer');
        Route::post('/{caisse}/mouvements', [CaisseController::class, 'storeMouvement'])->name('mouvements.store');

        // Deux exports distincts, sans collision de noms :
        //   /finances/caisses/12/export/csv
        //   /finances/caisses/12/export/pdf
        Route::get('/{caisse}/export/csv',  [CaisseController::class, 'export'])        ->name('export');
        Route::get('/{caisse}/export/pdf',  [CaisseController::class, 'exportPdf'])     ->name('exportPdf');
    });

    /* ==========================================================
     | 2.5 — Banques & Chèques
     |========================================================== */

    // --- Banques ---
    Route::prefix('banques')->name('banques.')->group(function () {
        Route::get('/',                     [BanqueController::class, 'index'])    ->name('index');
        Route::post('/',                    [BanqueController::class, 'store'])    ->name('store');
        Route::get('/{banque}',             [BanqueController::class, 'show'])     ->name('show');
        Route::put('/{banque}',             [BanqueController::class, 'update'])   ->name('update');
        Route::delete('/{banque}',          [BanqueController::class, 'destroy'])  ->name('destroy');
        Route::get('/{banque}/export-pdf',  [BanqueController::class, 'exportPdf'])->name('exportPdf');

        // --- Rapprochement bancaire (imbriqué dans une banque) ---
        Route::prefix('{banque}/rapprochement')->name('rapprochement.')->group(function () {
            Route::get('/',         [RapprochementController::class, 'index'])    ->name('index');
            Route::post('/analyser',[RapprochementController::class, 'analyser']) ->name('analyser');
            Route::post('/appliquer',[RapprochementController::class, 'appliquer'])->name('appliquer');
        });
    });

    // --- Chèques ---
    // /export doit être déclaré AVANT /{cheque}, sinon il est pris pour un ID.
    Route::prefix('cheques')->name('cheques.')->group(function () {
        Route::get('/',                     [ChequeController::class, 'index'])     ->name('index');
        Route::post('/',                    [ChequeController::class, 'store'])     ->name('store');
        Route::get('/export',               [ChequeController::class, 'export'])    ->name('export');
        Route::post('/{cheque}/rapprocher', [ChequeController::class, 'rapprocher'])->name('rapprocher');
    });

    /* ==========================================================
     | 2.6 — Dépenses
     |========================================================== */
    // /export doit être déclaré AVANT /{depense}, sinon il est pris pour un ID.
    Route::prefix('depenses')->name('depenses.')->group(function () {
        Route::get('/',                     [DepenseController::class, 'index'])   ->name('index');
        Route::post('/',                    [DepenseController::class, 'store'])   ->name('store');
        Route::get('/export',               [DepenseController::class, 'export'])  ->name('export');
        Route::get('/export-pdf',           [DepenseController::class, 'exportPdf'])->name('exportPdf');
        Route::put('/{depense}',            [DepenseController::class, 'update'])  ->name('update');
        Route::delete('/{depense}',         [DepenseController::class, 'destroy']) ->name('destroy');
        Route::post('/{depense}/valider',   [DepenseController::class, 'valider']) ->name('valider');
        Route::post('/{depense}/rejeter',   [DepenseController::class, 'rejeter']) ->name('rejeter');
        Route::post('/{depense}/regler',    [DepenseController::class, 'regler'])  ->name('regler');
        // Fiche de la dépense : détail et ventilation analytique (§ 6 « Ventilation analytique systématique »).
        Route::get('/{depense}',            [DepenseController::class, 'show'])    ->name('show')->whereNumber('depense');
    });

   /* ==========================================================
 | 2.7 — Comptabilité analytique
 |========================================================== */

// Référentiel des centres
Route::prefix('analytique/centres')->name('analytique.centres.')->group(function () {
    Route::get('/',                [CentreAnalytiqueController::class, 'index'])  ->name('index');
    Route::post('/',               [CentreAnalytiqueController::class, 'store'])  ->name('store');
    Route::put('/{centre}',        [CentreAnalytiqueController::class, 'update']) ->name('update');
    Route::delete('/{centre}',     [CentreAnalytiqueController::class, 'destroy'])->name('destroy');
});

// Rapports
Route::prefix('analytique/rapports')->name('analytique.rapports.')->group(function () {
    Route::get('/',                [RapportAnalytiqueController::class, 'index'])    ->name('index');
    Route::get('/export',          [RapportAnalytiqueController::class, 'export'])   ->name('export');
    Route::get('/export-pdf',      [RapportAnalytiqueController::class, 'exportPdf'])->name('exportPdf');
});
Route::post('/ventiler/{depense}', [VentilationController::class, 'ventilerDepense'])
    ->name('ventiler');


/* ==========================================================
 | 2.7 — Comptabilité analytique
 |========================================================== */

Route::prefix('analytique')->name('analytique.')->group(function () {

    // Centres analytiques
    Route::prefix('centres')->name('centres.')->group(function () {
        Route::get('/',            [CentreAnalytiqueController::class, 'index'])  ->name('index');
        Route::post('/',           [CentreAnalytiqueController::class, 'store'])  ->name('store');
        Route::put('/{centre}',    [CentreAnalytiqueController::class, 'update']) ->name('update');
        Route::delete('/{centre}', [CentreAnalytiqueController::class, 'destroy'])->name('destroy');
    });

    // Rapports
    Route::prefix('rapports')->name('rapports.')->group(function () {
        Route::get('/',           [RapportAnalytiqueController::class, 'index'])    ->name('index');
        Route::get('/export',     [RapportAnalytiqueController::class, 'export'])   ->name('export');
        Route::get('/export-pdf', [RapportAnalytiqueController::class, 'exportPdf'])->name('exportPdf');
   Route::get('/dashboard', [RapportAnalytiqueController::class, 'dashboard'])->name('dashboard');
        });

        Route::get('/export-excel', [RapportAnalytiqueController::class, 'exportExcel'])->name('exportExcel');
});



Route::prefix('previsions')->name('previsions.')->group(function () {
    Route::get('/',               [PrevisionController::class, 'index'])  ->name('index');
    Route::post('/',              [PrevisionController::class, 'store'])  ->name('store');
    Route::put('/{prevision}',    [PrevisionController::class, 'update']) ->name('update');
    Route::delete('/{prevision}', [PrevisionController::class, 'destroy'])->name('destroy');
});


Route::prefix('regles')->name('regles.')->group(function () {
    Route::get('/',            [RegleVentilationController::class, 'index'])  ->name('index');
    Route::post('/',           [RegleVentilationController::class, 'store'])  ->name('store');
    Route::put('/{regle}',     [RegleVentilationController::class, 'update']) ->name('update');
    Route::delete('/{regle}',  [RegleVentilationController::class, 'destroy'])->name('destroy');
});


Route::prefix('recouvrement')->name('recouvrement.')->group(function () {
    Route::get('/',              [RecouvrementController::class, 'index'])      ->name('index');
    Route::get('/export',        [RecouvrementController::class, 'export'])     ->name('export');
    Route::post('/recalculer',   [RecouvrementController::class, 'recalculer']) ->name('recalculer');
    Route::get('/{dossier}',     [RecouvrementController::class, 'show'])       ->name('show');
    Route::post('/{dossier}/relancer', [RecouvrementController::class, 'storeRelance'])->name('relancer');
    Route::post('/{dossier}/statut',   [RecouvrementController::class, 'changerStatut'])->name('statut');
});


});