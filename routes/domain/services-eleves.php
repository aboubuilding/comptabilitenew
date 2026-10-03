<?php

use App\Http\Controllers\Domain\ServicesEleves\AbonnementBusController;
use App\Http\Controllers\Domain\ServicesEleves\ActiviteController;
use App\Http\Controllers\Domain\ServicesEleves\CarburantVehiculeController;
use App\Http\Controllers\Domain\ServicesEleves\ChauffeurController;
use App\Http\Controllers\Domain\ServicesEleves\DashboardActiviteController;
use App\Http\Controllers\Domain\ServicesEleves\DashboardBibliothequeController;
use App\Http\Controllers\Domain\ServicesEleves\DashboardCantineController;
use App\Http\Controllers\Domain\ServicesEleves\DashboardTransportController;
use App\Http\Controllers\Domain\ServicesEleves\EntretienVehiculeController;
use App\Http\Controllers\Domain\ServicesEleves\EvenementController;
use App\Http\Controllers\Domain\ServicesEleves\InscriptionCantineController;
use App\Http\Controllers\Domain\ServicesEleves\LigneController;
use App\Http\Controllers\Domain\ServicesEleves\LivreController;
use App\Http\Controllers\Domain\ServicesEleves\MenuController;
use App\Http\Controllers\Domain\ServicesEleves\PreparationRepasController;
use App\Http\Controllers\Domain\ServicesEleves\PretController;
use App\Http\Controllers\Domain\ServicesEleves\ReferentielLivreController;
use App\Http\Controllers\Domain\ServicesEleves\VoitureController;
use App\Http\Controllers\Domain\ServicesEleves\ZoneController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Services aux Élèves — préfixe /services-eleves, noms services-eleves.*
|--------------------------------------------------------------------------
| Chaque sous-menu a son propre préfixe et son propre namespace de noms :
|   services-eleves.transport.*
|   services-eleves.cantine.*
|   services-eleves.bibliotheque.*
|   services-eleves.activites-evenements.*
|
| Les routes littérales (/export, /export-pdf, /dashboard, /rechercher)
| sont TOUJOURS déclarées AVANT les routes paramétrées (/{id}) dans le même
| groupe — sinon Laravel les prend pour un ID.
*/

Route::prefix('services-eleves')->name('services-eleves.')->group(function () {

    /* ==========================================================
     | 4.1 TRANSPORT SCOLAIRE
     |========================================================== */
    Route::prefix('transport')->name('transport.')->group(function () {

        /* ----- Véhicules ----- */
        Route::prefix('voitures')->name('voitures.')->group(function () {
            Route::get('/',              [VoitureController::class, 'index'])   ->name('index');
            Route::get('/export',        [VoitureController::class, 'export'])  ->name('export');
            Route::post('/',             [VoitureController::class, 'store'])   ->name('store');
            Route::get('/{voiture}',     [VoitureController::class, 'show'])    ->name('show');
            Route::put('/{voiture}',     [VoitureController::class, 'update'])  ->name('update');
            Route::delete('/{voiture}',  [VoitureController::class, 'destroy']) ->name('destroy');
            Route::post('/{voiture}/affecter', [VoitureController::class, 'affecter'])->name('affecter');
        });

        /* ----- Chauffeurs ----- */
        Route::prefix('chauffeurs')->name('chauffeurs.')->group(function () {
            Route::get('/',                [ChauffeurController::class, 'index'])   ->name('index');
            Route::post('/',               [ChauffeurController::class, 'store'])   ->name('store');
            Route::get('/{chauffeur}',     [ChauffeurController::class, 'show'])    ->name('show');
            Route::put('/{chauffeur}',     [ChauffeurController::class, 'update'])  ->name('update');
            Route::delete('/{chauffeur}',  [ChauffeurController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Zones ----- */
        Route::prefix('zones')->name('zones.')->group(function () {
            Route::get('/',          [ZoneController::class, 'index'])   ->name('index');
            Route::post('/',         [ZoneController::class, 'store'])   ->name('store');
            Route::put('/{zone}',    [ZoneController::class, 'update'])  ->name('update');
            Route::delete('/{zone}', [ZoneController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Lignes ----- */
        Route::prefix('lignes')->name('lignes.')->group(function () {
            Route::get('/',           [LigneController::class, 'index'])   ->name('index');
            Route::post('/',          [LigneController::class, 'store'])   ->name('store');
            Route::put('/{ligne}',    [LigneController::class, 'update'])  ->name('update');
            Route::delete('/{ligne}', [LigneController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Abonnements bus ----- */
        Route::prefix('abonnements')->name('abonnements.')->group(function () {
            Route::get('/',                         [AbonnementBusController::class, 'index'])     ->name('index');
            Route::get('/export',                   [AbonnementBusController::class, 'export'])    ->name('export');
            Route::post('/',                        [AbonnementBusController::class, 'store'])     ->name('store');
            Route::post('/{abonnement}/abandonner', [AbonnementBusController::class, 'abandonner'])->name('abandonner');
            Route::post('/{abonnement}/assigner',   [AbonnementBusController::class, 'assigner'])  ->name('assigner');
        });

        /* ----- Entretiens véhicules ----- */
        Route::prefix('entretiens')->name('entretiens.')->group(function () {
            Route::get('/',               [EntretienVehiculeController::class, 'index'])   ->name('index');
            Route::get('/export',         [EntretienVehiculeController::class, 'export'])  ->name('export');
            Route::post('/',              [EntretienVehiculeController::class, 'store'])   ->name('store');
            Route::put('/{entretien}',    [EntretienVehiculeController::class, 'update'])  ->name('update');
            Route::delete('/{entretien}', [EntretienVehiculeController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Carburants ----- */
        Route::prefix('carburants')->name('carburants.')->group(function () {
            Route::get('/',               [CarburantVehiculeController::class, 'index'])   ->name('index');
            Route::get('/export',         [CarburantVehiculeController::class, 'export'])  ->name('export');
            Route::post('/',              [CarburantVehiculeController::class, 'store'])   ->name('store');
            Route::put('/{carburant}',    [CarburantVehiculeController::class, 'update'])  ->name('update');
            Route::delete('/{carburant}', [CarburantVehiculeController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Assurances véhicules ----- */
        Route::prefix('assurances')->name('assurances.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\Domain\ServicesEleves\AssuranceVehiculeController::class, 'index'])   ->name('index');
            Route::get('/export',         [\App\Http\Controllers\Domain\ServicesEleves\AssuranceVehiculeController::class, 'export'])  ->name('export');
            Route::post('/',              [\App\Http\Controllers\Domain\ServicesEleves\AssuranceVehiculeController::class, 'store'])   ->name('store');
            Route::put('/{assurance}',    [\App\Http\Controllers\Domain\ServicesEleves\AssuranceVehiculeController::class, 'update'])  ->name('update');
            Route::delete('/{assurance}', [\App\Http\Controllers\Domain\ServicesEleves\AssuranceVehiculeController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Export Excel multi-feuilles du parc ----- */
        Route::get('/parc/export-excel', [VoitureController::class, 'exportParc'])
            ->name('parc.exportExcel');

        /* ----- Dashboard Transport ----- */
        Route::get('/dashboard', [DashboardTransportController::class, 'index'])
            ->name('dashboard');
    });

    /* ==========================================================
     | 4.2 CANTINE SCOLAIRE
     |========================================================== */
    Route::prefix('cantine')->name('cantine.')->group(function () {

        /* ----- Inscriptions cantine ----- */
        Route::prefix('inscriptions')->name('inscriptions.')->group(function () {
            Route::get('/',                          [InscriptionCantineController::class, 'index'])    ->name('index');
            Route::get('/export',                    [InscriptionCantineController::class, 'export'])   ->name('export');
            Route::post('/',                         [InscriptionCantineController::class, 'store'])    ->name('store');
            Route::post('/{inscription}/abandonner', [InscriptionCantineController::class, 'abandonner'])->name('abandonner');
        });

        /* ----- Menus ----- */
        Route::prefix('menus')->name('menus.')->group(function () {
            Route::get('/',          [MenuController::class, 'index'])   ->name('index');
            Route::post('/',         [MenuController::class, 'store'])   ->name('store');
            Route::get('/{menu}',    [MenuController::class, 'show'])    ->name('show');
            Route::put('/{menu}',    [MenuController::class, 'update'])  ->name('update');
            Route::delete('/{menu}', [MenuController::class, 'destroy']) ->name('destroy');
        });

        /* ----- Préparations ----- */
        Route::prefix('preparations')->name('preparations.')->group(function () {
            Route::get('/',       [PreparationRepasController::class, 'index'])  ->name('index');
            Route::get('/export', [PreparationRepasController::class, 'export']) ->name('export');
            Route::post('/',      [PreparationRepasController::class, 'store'])  ->name('store');
        });

        /* ----- Dashboard Cantine ----- */
        Route::get('/dashboard', [DashboardCantineController::class, 'index'])
            ->name('dashboard');
    });

    /* ==========================================================
     | 4.3 BIBLIOTHÈQUE
     |========================================================== */
    Route::prefix('bibliotheque')->name('bibliotheque.')->group(function () {

        /* ----- Catalogue livres ----- */
        // /export et /export-pdf AVANT /{livre}
        Route::prefix('livres')->name('livres.')->group(function () {
            Route::get('/',            [LivreController::class, 'index'])    ->name('index');
            Route::get('/export',      [LivreController::class, 'export'])   ->name('export');
            Route::get('/export-pdf',  [LivreController::class, 'exportPdf'])->name('exportPdf');
            Route::post('/',           [LivreController::class, 'store'])    ->name('store');
            Route::get('/{livre}',     [LivreController::class, 'show'])     ->name('show');
            Route::put('/{livre}',     [LivreController::class, 'update'])   ->name('update');
            Route::delete('/{livre}',  [LivreController::class, 'destroy'])  ->name('destroy');
        });

        /* ----- Prêts ----- */
        // /export et /recalculer-retards AVANT /{pret}
        Route::prefix('prets')->name('prets.')->group(function () {
            Route::get('/',                     [PretController::class, 'index'])            ->name('index');
            Route::get('/export',               [PretController::class, 'export'])           ->name('export');
            Route::post('/recalculer-retards',  [PretController::class, 'recalculerRetards'])->name('recalculerRetards');
            Route::post('/',                    [PretController::class, 'store'])            ->name('store');
            Route::post('/{pret}/retour',       [PretController::class, 'retour'])           ->name('retour');
            Route::post('/{pret}/perdu',        [PretController::class, 'perdu'])            ->name('perdu');
        });

        /* ----- Référentiels ----- */
        Route::prefix('referentiels')->name('referentiels.')->group(function () {
            Route::get('/',                [ReferentielLivreController::class, 'index'])  ->name('index');
            Route::post('/',               [ReferentielLivreController::class, 'store'])  ->name('store');
            Route::put('/{type}/{id}',     [ReferentielLivreController::class, 'update']) ->name('update');
            Route::delete('/{type}/{id}',  [ReferentielLivreController::class, 'destroy'])->name('destroy');
        });

        /* ----- Dashboard Bibliothèque ----- */
        Route::get('/dashboard', [DashboardBibliothequeController::class, 'index'])
            ->name('dashboard');
    });

    /* ==========================================================
     | 4.4 ACTIVITÉS & ÉVÉNEMENTS
     |========================================================== */
    Route::prefix('activites-evenements')->name('activites-evenements.')->group(function () {

        /* ----- Activités parascolaires ----- */
        // /export et /souscrire AVANT /{activite}
        Route::prefix('activites')->name('activites.')->group(function () {
            Route::get('/',               [ActiviteController::class, 'index'])   ->name('index');
            Route::get('/export',         [ActiviteController::class, 'export'])  ->name('export');
            Route::post('/souscrire',     [ActiviteController::class, 'souscrire'])->name('souscrire');
            Route::post('/',              [ActiviteController::class, 'store'])   ->name('store');
            Route::put('/{activite}',     [ActiviteController::class, 'update'])  ->name('update');
            Route::delete('/{activite}',  [ActiviteController::class, 'destroy']) ->name('destroy');
            Route::post('/inscriptions/{inscription}/annuler',
                [ActiviteController::class, 'annulerInscription']
            )->name('annulerInscription');
        });

        /* ----- Liste des inscriptions activités ----- */
        Route::get('/inscriptions', [ActiviteController::class, 'inscriptions'])
            ->name('inscriptions.index');

        /* ----- Événements ----- */
        // /export et /pre-inscrire AVANT /{evenement}
        Route::prefix('evenements')->name('evenements.')->group(function () {
            Route::get('/',                [EvenementController::class, 'index'])   ->name('index');
            Route::get('/export',          [EvenementController::class, 'export'])  ->name('export');
            Route::post('/pre-inscrire',   [EvenementController::class, 'preInscrire'])->name('preInscrire');
            Route::post('/',               [EvenementController::class, 'store'])   ->name('store');
            Route::get('/{evenement}',     [EvenementController::class, 'show'])    ->name('show');
            Route::put('/{evenement}',     [EvenementController::class, 'update'])  ->name('update');
            Route::delete('/{evenement}',  [EvenementController::class, 'destroy']) ->name('destroy');
            Route::post('/participations/{participation}/annuler',
                [EvenementController::class, 'annulerPreInscription']
            )->name('annulerPreInscription');
        });

        /* ----- Dashboard Activités & Événements ----- */
        Route::get('/dashboard', [DashboardActiviteController::class, 'index'])
            ->name('dashboard');
    });
});