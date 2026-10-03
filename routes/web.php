<?php

use App\Http\Middleware\ResolveAnneeScolaire;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques (non protégées)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

// Racine de l'application : redirige vers /tableau, qui est protégé par
// 'auth' — un visiteur non connecté est donc automatiquement renvoyé
// vers /login par la chaîne de redirections, sans dupliquer cette
// logique ici.
Route::redirect('/', '/tableau');

/*
|--------------------------------------------------------------------------
| Routes admin — protégées ici, UNE SEULE FOIS
|--------------------------------------------------------------------------
| 'auth'                : non connecté -> redirigé vers /login
| ResolveAnneeScolaire  : résout/alimente l'année scolaire active pour
|                         chaque requête protégée (voir le middleware)
|
| Chaque fichier de routes/admin/ est require-é DANS ce groupe : aucun
| module ne peut donc se retrouver non protégé par oubli local.
*/
Route::middleware(['auth', ResolveAnneeScolaire::class])->group(function () {

    // Référencé partout (header, footer, redirection post-login) mais
    // jamais encore défini — stub minimal, à remplacer par un vrai
    // DashboardController le moment venu.
   Route::get('/tableau', [\App\Http\Controllers\Domain\TableauController::class, 'tableau'])
        ->name('tableau');

    // Recherche globale (fenêtre Ctrl+K de l'en-tête)
    Route::get('/recherche', \App\Http\Controllers\Domain\RechercheController::class)->name('recherche');

    // Mon profil : chaque utilisateur consulte son compte et change son mot de passe
    Route::get('/profil', [\App\Http\Controllers\Domain\Administration\ProfilController::class, 'show'])->name('profil.show');
    Route::post('/profil/mot-de-passe', [\App\Http\Controllers\Domain\Administration\ProfilController::class, 'changerMotDePasse'])->name('profil.mot-de-passe');

    require __DIR__.'/domain/scolarite.php';
    require __DIR__.'/domain/finances.php';
    require __DIR__.'/domain/logistique.php';
    require __DIR__.'/domain/services-eleves.php';
     require __DIR__.'/domain/rh.php';
   require __DIR__.'/domain/administration.php';
    // -> à décommenter au fur et à mesure que ces fichiers de routes existent
});