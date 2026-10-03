<?php

use App\Http\Controllers\Domain\Administration\JournalController;
use App\Http\Controllers\Domain\Administration\UserController;
use App\Http\Controllers\Domain\Administration\MessageController;
use App\Http\Controllers\Domain\Administration\ModeleMessageController;
use App\Http\Controllers\Domain\Administration\NationaliteController;
use Illuminate\Support\Facades\Route;

Route::prefix('administration')->name('administration.')->group(function () {

    // 6.1 Utilisateurs & Profils
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/export', [UserController::class, 'export'])->name('export');
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    // 6.2 Référentiels & Paramètres généraux — nationalités
    Route::prefix('nationalites')->name('nationalites.')->group(function () {
        Route::get('/', [NationaliteController::class, 'index'])->name('index');
        Route::post('/', [NationaliteController::class, 'store'])->name('store');
        Route::put('/{nationalite}', [NationaliteController::class, 'update'])->name('update');
        Route::delete('/{nationalite}', [NationaliteController::class, 'destroy'])->name('destroy');
    });

    // 6.4 Journal & Traçabilité
    Route::prefix('journal')->name('journal.')->group(function () {
        Route::get('/export', [JournalController::class, 'export'])->name('export');
        Route::get('/', [JournalController::class, 'index'])->name('index');
    });


    Route::prefix('modeles')->name('modeles.')->group(function () {
        Route::get('/', [ModeleMessageController::class, 'index'])->name('index');
        Route::get('/create', [ModeleMessageController::class, 'create'])->name('create');
        Route::post('/', [ModeleMessageController::class, 'store'])->name('store');
        Route::get('/{modele}/edit', [ModeleMessageController::class, 'edit'])->name('edit');
        Route::put('/{modele}', [ModeleMessageController::class, 'update'])->name('update');
        Route::delete('/{modele}', [ModeleMessageController::class, 'destroy'])->name('destroy');
    });

    // 6.3 Communications — Messages
    Route::prefix('messages')->name('messages.')->group(function () {
        // ⚠️ routes fixes AVANT /{message}
        Route::get('/export',    [MessageController::class, 'export'])->name('export');
        Route::get('/historique', [MessageController::class, 'historique'])->name('historique');
        Route::get('/',          [MessageController::class, 'index'])->name('index');
        Route::get('/create',    [MessageController::class, 'create'])->name('create');
        Route::post('/',         [MessageController::class, 'store'])->name('store');
        Route::get('/{message}', [MessageController::class, 'show'])->name('show');
        Route::get('/{message}/edit', [MessageController::class, 'edit'])->name('edit');
        Route::put('/{message}', [MessageController::class, 'update'])->name('update');
        Route::post('/{message}/envoyer', [MessageController::class, 'envoyer'])->name('envoyer');
        Route::delete('/{message}', [MessageController::class, 'destroy'])->name('destroy');
    });
});