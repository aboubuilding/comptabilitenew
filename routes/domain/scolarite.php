<?php

use App\Http\Controllers\Domain\Scolarite\AnneeController;
use App\Http\Controllers\Domain\Scolarite\ClasseController;
use App\Http\Controllers\Domain\Scolarite\CycleController;
use App\Http\Controllers\Domain\Scolarite\NiveauController;
use App\Http\Controllers\Domain\Scolarite\EleveController;
use App\Http\Controllers\Domain\Scolarite\EspaceController;
use App\Http\Controllers\Domain\Scolarite\InscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes Scolarité — préfixe /scolarite, noms scolarite.*
|--------------------------------------------------------------------------
| Déclarées explicitement (pas de Route::resource) : chaque route liste
| son verbe HTTP et son URI, sans deviner ce qu'un ->except() retire. A
| require depuis routes/web.php, dans le groupe
| middleware(['auth', ResolveAnneeScolaire::class]) commun à
| routes/admin/.
|
| Mêmes noms de route qu'avant (scolarite.cycles.index, etc.) : rien à
| changer dans les vues ni les Controllers, qui utilisent route(...) et
| non des URLs en dur.
*/

Route::prefix('scolarite')->name('scolarite.')->group(function () {

    // Cycles — ajout/modification via modale AJAX, pas de page 'create'
    Route::get('/cycles', [CycleController::class, 'index'])->name('cycles.index');
    Route::get('/cycles/{cycle}/edit', [CycleController::class, 'edit'])->name('cycles.edit');
    Route::post('/cycles', [CycleController::class, 'store'])->name('cycles.store');
    Route::put('/cycles/{cycle}', [CycleController::class, 'update'])->name('cycles.update');
    Route::delete('/cycles/{cycle}', [CycleController::class, 'destroy'])->name('cycles.destroy');

    // Niveaux — ajout/modification via modale AJAX désormais aussi,
    // même pattern que Cycles.
    Route::get('/niveaux', [NiveauController::class, 'index'])->name('niveaux.index');
    Route::get('/niveaux/{niveau}/edit', [NiveauController::class, 'edit'])->name('niveaux.edit');
    Route::post('/niveaux', [NiveauController::class, 'store'])->name('niveaux.store');
    Route::put('/niveaux/{niveau}', [NiveauController::class, 'update'])->name('niveaux.update');
    Route::delete('/niveaux/{niveau}', [NiveauController::class, 'destroy'])->name('niveaux.destroy');

    // Classes de l'année scolaire de travail — même fonctionnement que les niveaux.
    Route::get('/classes', [ClasseController::class, 'index'])->name('classes.index');
    Route::get('/classes/{classe}/edit', [ClasseController::class, 'edit'])->name('classes.edit');
    Route::post('/classes', [ClasseController::class, 'store'])->name('classes.store');
    Route::put('/classes/{classe}', [ClasseController::class, 'update'])->name('classes.update');
    Route::delete('/classes/{classe}', [ClasseController::class, 'destroy'])->name('classes.destroy');

    // Années scolaires — Administrateur seul (cahier des charges §4, 1.4).
    // Ajout/modification via modale ; 'cloturer' est une action métier
    // distincte de 'update', pas une suppression.
    Route::get('/annees', [AnneeController::class, 'index'])->name('annees.index');
    Route::get('/annees/{annee}/edit', [AnneeController::class, 'edit'])->name('annees.edit');
    Route::post('/annees', [AnneeController::class, 'store'])->name('annees.store');
    Route::put('/annees/{annee}', [AnneeController::class, 'update'])->name('annees.update');
    Route::post('/annees/{annee}/ouvrir', [AnneeController::class, 'ouvrir'])->name('annees.ouvrir');
    Route::post('/annees/{annee}/cloturer', [AnneeController::class, 'cloturer'])->name('annees.cloturer');

// Élèves — pages create/edit séparées (trop de champs pour une
   Route::prefix('eleves')->name('eleves.')->group(function () {
        // ⚠️ routes fixes AVANT /{eleve}
        Route::get('/export',        [EleveController::class, 'export'])->name('export');
        Route::get('/affecter-form', [EleveController::class, 'affecterForm'])->name('affecter-form');
        Route::post('/affecter',     [EleveController::class, 'affecter'])->name('affecter');
        Route::get('/',              [EleveController::class, 'index'])->name('index');
        Route::get('/create',        [EleveController::class, 'create'])->name('create');
        Route::post('/',             [EleveController::class, 'store'])->name('store');
        Route::get('/{eleve}/fiche-pdf', [EleveController::class, 'fichePdf'])->name('fiche-pdf');
        Route::get('/{eleve}/edit',  [EleveController::class, 'edit'])->name('edit');
        Route::get('/{eleve}',       [EleveController::class, 'show'])->name('show');
        Route::put('/{eleve}',       [EleveController::class, 'update'])->name('update');
        Route::delete('/{eleve}',    [EleveController::class, 'destroy'])->name('destroy');
    });
    // Espaces familiaux — ajout/modification via modale (même pattern
    // que Cycle). 'archiver' plutôt que 'destroy'.
    Route::get('/espaces', [EspaceController::class, 'index'])->name('espaces.index');
    Route::get('/espaces/{espace}/edit', [EspaceController::class, 'edit'])->name('espaces.edit');
    Route::post('/espaces', [EspaceController::class, 'store'])->name('espaces.store');
    Route::put('/espaces/{espace}', [EspaceController::class, 'update'])->name('espaces.update');
    Route::post('/espaces/{espace}/archiver', [EspaceController::class, 'archiver'])->name('espaces.archiver');

     // Inscriptions — pas d'edit/destroy : le cycle de vie passe par des
    // actions dédiées (valider/rejeter/abandon), pas une modification
    // générique (cahier des charges §4, 1.3).
    Route::get('/inscriptions', [InscriptionController::class, 'index'])->name('inscriptions.index');
    Route::post('/inscriptions', [InscriptionController::class, 'store'])->name('inscriptions.store');
    Route::post('/inscriptions/{inscription}/valider', [InscriptionController::class, 'valider'])->name('inscriptions.valider');
    Route::post('/inscriptions/{inscription}/rejeter', [InscriptionController::class, 'rejeter'])->name('inscriptions.rejeter');
    Route::post('/inscriptions/{inscription}/abandon', [InscriptionController::class, 'enregistrerAbandon'])->name('inscriptions.abandon');
    Route::get('/inscriptions/{inscription}/certificat', [InscriptionController::class, 'certificat'])->name('inscriptions.certificat');
    
// 1.2 Parents & Familles
    Route::prefix('espaces')->name('espaces.')->group(function () {

        // Regroupement
        Route::get('/regrouper-form', [EspaceController::class, 'regrouperForm'])->name('regrouper-form');
        Route::post('/regrouper',     [EspaceController::class, 'regrouper'])->name('regrouper');

        // Export
        Route::get('/export', [EspaceController::class, 'export'])->name('export');

        // CRUD espaces
        Route::get('/',       [EspaceController::class, 'index'])->name('index');
        Route::get('/create', [EspaceController::class, 'create'])->name('create');
        Route::post('/',      [EspaceController::class, 'store'])->name('store');

        // Parents (imbriqués dans un espace)
        Route::prefix('{espace}/parents')->name('parents.')->group(function () {
            Route::get('/create',              [EspaceController::class, 'parentCreate'])->name('create');
            Route::post('/',                   [EspaceController::class, 'parentStore'])->name('store');
            Route::get('/{parent}/edit',       [EspaceController::class, 'parentEdit'])->name('edit');
            Route::put('/{parent}',            [EspaceController::class, 'parentUpdate'])->name('update');
            Route::post('/{parent}/principal', [EspaceController::class, 'parentDesignerPrincipal'])->name('principal');
            Route::delete('/{parent}',         [EspaceController::class, 'parentDestroy'])->name('destroy');
        });

        // Comptes portail (imbriqués dans un espace)
        Route::prefix('{espace}/comptes')->name('comptes.')->group(function () {
            Route::get('/create',              [EspaceController::class, 'compteCreate'])->name('create');
            Route::post('/',                   [EspaceController::class, 'compteStore'])->name('store');
            Route::get('/{compte}/edit',       [EspaceController::class, 'compteEdit'])->name('edit');
            Route::put('/{compte}',            [EspaceController::class, 'compteUpdate'])->name('update');
            Route::post('/{compte}/regenerer', [EspaceController::class, 'compteRegenerer'])->name('regenerer');
            Route::delete('/{compte}',         [EspaceController::class, 'compteDestroy'])->name('destroy');
        });

        // Détail / édition / suppression d'un espace (APRÈS les routes imbriquées)
        Route::get('/{espace}',      [EspaceController::class, 'show'])->name('show');
        Route::get('/{espace}/edit', [EspaceController::class, 'edit'])->name('edit');
        Route::put('/{espace}',      [EspaceController::class, 'update'])->name('update');
        Route::delete('/{espace}',   [EspaceController::class, 'destroy'])->name('destroy');
    });




    });


