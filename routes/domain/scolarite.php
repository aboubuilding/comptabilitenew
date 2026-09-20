<?php

use App\Http\Controllers\Admin\Scolarite\AnneeController;
use App\Http\Controllers\Admin\Scolarite\CycleController;
use App\Http\Controllers\Admin\Scolarite\NiveauController;
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

    // Années scolaires — Administrateur seul (cahier des charges §4, 1.4).
    // Ajout/modification via modale ; 'cloturer' est une action métier
    // distincte de 'update', pas une suppression.
    Route::get('/annees', [AnneeController::class, 'index'])->name('annees.index');
    Route::get('/annees/{annee}/edit', [AnneeController::class, 'edit'])->name('annees.edit');
    Route::post('/annees', [AnneeController::class, 'store'])->name('annees.store');
    Route::put('/annees/{annee}', [AnneeController::class, 'update'])->name('annees.update');
    Route::post('/annees/{annee}/cloturer', [AnneeController::class, 'cloturer'])->name('annees.cloturer');

// Élèves — pages create/edit séparées (trop de champs pour une
    // modale), 'archiver' plutôt que 'destroy' (cahier des charges).
    Route::get('/eleves', [EleveController::class, 'index'])->name('eleves.index');
    Route::get('/eleves/create', [EleveController::class, 'create'])->name('eleves.create');
    Route::get('/eleves/{eleve}/edit', [EleveController::class, 'edit'])->name('eleves.edit');
    Route::post('/eleves', [EleveController::class, 'store'])->name('eleves.store');
    Route::put('/eleves/{eleve}', [EleveController::class, 'update'])->name('eleves.update');
    Route::post('/eleves/{eleve}/archiver', [EleveController::class, 'archiver'])->name('eleves.archiver');


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
    
    });


