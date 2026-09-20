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
   Route::get('/tableau', [\App\Http\Controllers\Admin\TableauController::class, 'tableau'])
        ->name('tableau');

    require __DIR__.'/domain/scolarite.php';
    require __DIR__.'/domain/finances.php';
    // require __DIR__.'/admin/logistique.php';
    // require __DIR__.'/admin/services-eleves.php';
    // require __DIR__.'/admin/rh.php';
    // require __DIR__.'/admin/administration.php';
    // -> à décommenter au fur et à mesure que ces fichiers de routes existent
});