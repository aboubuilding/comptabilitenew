<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes d'authentification
|--------------------------------------------------------------------------
| Hors routes/admin/ : ce n'est pas un domaine métier. A require depuis
| routes/web.php, en dehors du groupe middleware(['auth', ResolveAnneeScolaire::class])
| qui protège tous les fichiers de routes/admin/.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')
    ->post('/logout', [LoginController::class, 'destroy'])
    ->name('logout');