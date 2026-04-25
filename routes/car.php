<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware(['admin', 'comptable', 'directeur'])->group(function () {


    //----------------- Paiements liés aux bus


    Route::get('/paiements/parc', [\App\Http\Controllers\Admin\PaiementController::class, 'parc'])->name('admin_paiements_parc');

    //----------------- Voitures

    Route::get('/voitures/index', [App\Http\Controllers\Admin\VoitureController::class, 'index'])->name('admin_voitures_index');
    Route::get('/voitures/add', [App\Http\Controllers\Admin\VoitureController::class, 'add'])->name('admin_voitures_add');
    Route::post('/voitures/save', [App\Http\Controllers\Admin\VoitureController::class, 'store'])->name('admin_voitures_store');
    Route::get('/voitures/modifier/{id}', [App\Http\Controllers\Admin\VoitureController::class, 'edit'])->name('admin_voitures_edit');
    Route::post('/voitures/update/{id}', [App\Http\Controllers\Admin\VoitureController::class, 'update'])->name('admin_voitures_update');
    Route::post('/voitures/delete/{id}', [App\Http\Controllers\Admin\VoitureController::class, 'delete'])->name('admin_voitures_delete');




    //----------------- Chauffeurs

    Route::get('/chauffeurs/index', [App\Http\Controllers\Admin\ChauffeurController::class, 'index'])->name('admin_chauffeurs_index');
    Route::get('/chauffeurs/add', [App\Http\Controllers\Admin\ChauffeurController::class, 'add'])->name('admin_chauffeurs_add');
    Route::post('/chauffeurs/save', [App\Http\Controllers\Admin\ChauffeurController::class, 'store'])->name('admin_chauffeurs_store');
    Route::get('/chauffeurs/modifier/{id}', [App\Http\Controllers\Admin\ChauffeurController::class, 'edit'])->name('admin_chauffeurs_edit');
    Route::post('/chauffeurs/update/{id}', [App\Http\Controllers\Admin\ChauffeurController::class, 'update'])->name('admin_chauffeurs_update');
    Route::post('/chauffeurs/delete/{id}', [App\Http\Controllers\Admin\ChauffeurController::class, 'delete'])->name('admin_chauffeurs_delete');



    //----------------- Lignes

    Route::get('/lignes/index', [App\Http\Controllers\Admin\LigneController::class, 'index'])->name('admin_lignes_index');
    Route::get('/lignes/add', [App\Http\Controllers\Admin\LigneController::class, 'add'])->name('admin_lignes_add');
    Route::post('/lignes/save', [App\Http\Controllers\Admin\LigneController::class, 'store'])->name('admin_lignes_store');
    Route::get('/lignes/modifier/{id}', [App\Http\Controllers\Admin\LigneController::class, 'edit'])->name('admin_lignes_edit');
    Route::post('/lignes/update/{id}', [App\Http\Controllers\Admin\LigneController::class, 'update'])->name('admin_lignes_update');
    Route::post('/lignes/delete/{id}', [App\Http\Controllers\Admin\LigneController::class, 'delete'])->name('admin_lignes_delete');


           //----------------- Zones

           Route::get('/zones/index', [App\Http\Controllers\Admin\ZoneController::class, 'index'])->name('admin_zones_index');
           Route::get('/zones/add', [App\Http\Controllers\Admin\ZoneController::class, 'add'])->name('admin_zones_add');
           Route::post('/zones/save', [App\Http\Controllers\Admin\ZoneController::class, 'store'])->name('admin_zones_store');
           Route::get('/zones/modifier/{id}', [App\Http\Controllers\Admin\ZoneController::class, 'edit'])->name('admin_zones_edit');
           Route::post('/zones/update/{id}', [App\Http\Controllers\Admin\ZoneController::class, 'update'])->name('admin_zones_update');
           Route::post('/zones/delete/{id}', [App\Http\Controllers\Admin\ZoneController::class, 'delete'])->name('admin_zones_delete');


     //----------------- Cars

     Route::get('/cars/index', [App\Http\Controllers\Admin\CarController::class, 'index'])->name('admin_cars_index');
     Route::get('/cars/tableau', [App\Http\Controllers\Admin\CarController::class, 'tableau'])->name('admin_cars_tableau');
     Route::get('/cars/add', [App\Http\Controllers\Admin\CarController::class, 'add'])->name('admin_cars_add');
     Route::post('/cars/save', [App\Http\Controllers\Admin\CarController::class, 'store'])->name('admin_cars_store');
     Route::get('/cars/modifier/{id}', [App\Http\Controllers\Admin\CarController::class, 'edit'])->name('admin_cars_edit');
     Route::post('/cars/update/{id}', [App\Http\Controllers\Admin\CarController::class, 'update'])->name('admin_cars_update');
     Route::post('/cars/delete/{id}', [App\Http\Controllers\Admin\CarController::class, 'delete'])->name('admin_cars_delete');



});
