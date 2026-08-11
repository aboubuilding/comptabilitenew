<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\TableauController;
use App\Http\Controllers\Parametre\AnneeController;
use App\Http\Controllers\Parametre\UserController;
use App\Http\Controllers\Parametre\CycleController;
use App\Http\Controllers\Parametre\NiveauController;
use App\Http\Controllers\Parametre\FraisEcoleController;
use App\Http\Controllers\Parametre\EvenementController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Routes protégées
Route::middleware(['auth'])->group(function () {

    // Tableau de bord
    Route::get('/', [TableauController::class, 'tableau'])->name('tableau');
    Route::get('/dashboard', [TableauController::class, 'tableau'])->name('dashboard');

    // Profil
    Route::get('/profile', [TableauController::class, 'profile'])->name('profile');
    Route::put('/profile', [TableauController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [TableauController::class, 'updatePassword'])->name('profile.password');

    // Gestion des années scolaires
    Route::resource('annees', AnneeController::class)->except(['show']);
    Route::get('annees/{annee}/show', [AnneeController::class, 'show'])->name('annees.show');
    Route::post('annees/{annee}/toggle-active', [AnneeController::class, 'toggleActive'])->name('annees.toggleActive');
    Route::post('annees/{annee}/set-active', [AnneeController::class, 'setActive'])->name('annees.setActive');
    Route::post('annees/{annee}/toggle-status', [AnneeController::class, 'toggleStatus'])->name('annees.toggleStatus');
    Route::get('annees/stats', [AnneeController::class, 'stats'])->name('annees.stats');

    // Gestion des utilisateurs
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}/show', [UserController::class, 'show'])->name('users.show');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggleActive');
    Route::post('users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');
    Route::get('users/stats', [UserController::class, 'stats'])->name('users.stats');

    // Gestion des cycles
    Route::resource('cycles', CycleController::class)->except(['show']);
    Route::get('cycles/{cycle}/show', [CycleController::class, 'show'])->name('cycles.show');
    Route::post('cycles/{cycle}/toggle-active', [CycleController::class, 'toggleActive'])->name('cycles.toggleActive');
    Route::get('cycles/stats', [CycleController::class, 'stats'])->name('cycles.stats');


    // Gestion des niveaux
    Route::resource('niveaux', NiveauController::class)->except(['show']);
    Route::get('niveaux/{niveau}/show', [NiveauController::class, 'show'])->name('niveaux.show');
    Route::post('niveaux/{niveau}/toggle-active', [NiveauController::class, 'toggleActive'])->name('niveaux.toggleActive');
    Route::get('niveaux/stats', [NiveauController::class, 'stats'])->name('niveaux.stats');
    Route::get('niveaux/get-by-cycle', [NiveauController::class, 'getByCycle'])->name('niveaux.getByCycle');

    // Gestion des frais d'école
    Route::resource('frais-ecoles', FraisEcoleController::class)->except(['show']);
    Route::get('frais-ecoles/data', [FraisEcoleController::class, 'getData'])->name('frais-ecoles.data');
    Route::get('frais-ecoles/{frais_ecole}/show', [FraisEcoleController::class, 'show'])->name('frais-ecoles.show');
    Route::post('frais-ecoles/{frais_ecole}/toggle-active', [FraisEcoleController::class, 'toggleActive'])->name('frais-ecoles.toggleActive');
    Route::get('frais-ecoles/stats', [FraisEcoleController::class, 'stats'])->name('frais-ecoles.stats');
    Route::get('frais-ecoles/get-plans', [FraisEcoleController::class, 'getPlans'])->name('frais-ecoles.getPlans');

    // Gestion des événements
    Route::resource('evenements', EvenementController::class)->except(['show']);
    Route::get('evenements/data', [EvenementController::class, 'getData'])->name('evenements.data');
    Route::get('evenements/{evenement}/show', [EvenementController::class, 'show'])->name('evenements.show');
    Route::post('evenements/{evenement}/toggle-active', [EvenementController::class, 'toggleActive'])->name('evenements.toggleActive');
    Route::get('evenements/stats', [EvenementController::class, 'stats'])->name('evenements.stats');


});

// Route de vérification de session (AJAX)
Route::get('/check-session', [LoginController::class, 'checkSession'])->name('check.session');
