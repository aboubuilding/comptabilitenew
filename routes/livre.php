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




//----------------- Auteurs

Route::get('/auteurs/index', [App\Http\Controllers\Admin_old\AuteurController::class, 'index'])->name('admin_auteurs_index');
Route::get('/auteurs/modifier/{id}', [App\Http\Controllers\Admin_old\AuteurController::class, 'edit'])->name('admin_auteurs_edit');
Route::post('/auteurs/update/{id}', [App\Http\Controllers\Admin_old\AuteurController::class, 'update'])->name('admin_auteurs_update');
Route::post('/auteurs/delete/{id}', [App\Http\Controllers\Admin_old\AuteurController::class, 'delete'])->name('admin_auteurs_delete');
Route::get('/auteurs/add', [App\Http\Controllers\Admin_old\AuteurController::class, 'add'])->name('admin_auteurs_add');
Route::post('/auteurs/save', [App\Http\Controllers\Admin_old\AuteurController::class, 'store'])->name('admin_auteurs_store');



//----------------- Categories

Route::get('/categorielivres/index', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'index'])->name('admin_categorielivres_index');
Route::get('/categorielivres/modifier/{id}', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'edit'])->name('admin_categorielivres_edit');
Route::post('/categorielivres/update/{id}', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'update'])->name('admin_categorielivres_update');
Route::post('/categorielivres/delete/{id}', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'delete'])->name('admin_categorielivres_delete');
Route::get('/categorielivres/add', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'add'])->name('admin_categorielivres_add');
Route::post('/categorielivres/save', [App\Http\Controllers\Admin_old\CategorieLivreController::class, 'store'])->name('admin_categorielivres_store');


//----------------- Maison d editions

Route::get('/maisons/index', [App\Http\Controllers\Admin_old\MaisonController::class, 'index'])->name('admin_maisons_index');
Route::get('/maisons/modifier/{id}', [App\Http\Controllers\Admin_old\MaisonController::class, 'edit'])->name('admin_maisons_edit');
Route::post('/maisons/update/{id}', [App\Http\Controllers\Admin_old\MaisonController::class, 'update'])->name('admin_maisons_update');
Route::post('/maisons/delete/{id}', [App\Http\Controllers\Admin_old\MaisonController::class, 'delete'])->name('admin_maisons_delete');
Route::get('/maisons/add', [App\Http\Controllers\Admin_old\MaisonController::class, 'add'])->name('admin_maisons_add');
Route::post('/maisons/save', [App\Http\Controllers\Admin_old\MaisonController::class, 'store'])->name('admin_maisons_store');



//----------------- Livres

Route::get('/livres/index', [App\Http\Controllers\Admin_old\LivreController::class, 'index'])->name('admin_livres_index');
Route::get('/livres/modifier/{id}', [App\Http\Controllers\Admin_old\LivreController::class, 'edit'])->name('admin_livres_edit');
Route::get('/livres/detail/{id}', [App\Http\Controllers\Admin_old\LivreController::class, 'detail'])->name('admin_livres_detail');
Route::post('/livres/update/{id}', [App\Http\Controllers\Admin_old\LivreController::class, 'update'])->name('admin_livres_update');
Route::post('/livres/delete/{id}', [App\Http\Controllers\Admin_old\LivreController::class, 'delete'])->name('admin_livres_delete');
Route::get('/livres/add', [App\Http\Controllers\Admin_old\LivreController::class, 'add'])->name('admin_livres_add');
Route::post('/livres/save', [App\Http\Controllers\Admin_old\LivreController::class, 'store'])->name('admin_livres_store');


//----------------- Annee d editions

Route::get('/anneeditions/index', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'index'])->name('admin_anneeditions_index');
Route::get('/anneeditions/modifier/{id}', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'edit'])->name('admin_anneeditions_edit');
Route::post('/anneeditions/update/{id}', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'update'])->name('admin_anneeditions_update');
Route::post('/anneeditions/delete/{id}', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'delete'])->name('admin_anneeditions_delete');
Route::get('/anneeditions/add', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'add'])->name('admin_anneeditions_add');
Route::post('/anneeditions/save', [App\Http\Controllers\Admin_old\AnneeEditionController::class, 'store'])->name('admin_anneeditions_store');



//----------------- Prets

Route::get('/prets/index', [App\Http\Controllers\Admin_old\PretController::class, 'index'])->name('admin_prets_index');
Route::get('/prets/modifier/{id}', [App\Http\Controllers\Admin_old\PretController::class, 'edit'])->name('admin_prets_edit');
Route::get('/prets/detail/{id}', [App\Http\Controllers\Admin_old\PretController::class, 'detail'])->name('admin_prets_detail');
Route::post('/prets/update/{id}', [App\Http\Controllers\Admin_old\PretController::class, 'update'])->name('admin_prets_update');
Route::post('/prets/delete/{id}', [App\Http\Controllers\Admin_old\PretController::class, 'delete'])->name('admin_prets_delete');
Route::get('/prets/add', [App\Http\Controllers\Admin_old\PretController::class, 'add'])->name('admin_prets_add');
Route::post('/prets/save', [App\Http\Controllers\Admin_old\PretController::class, 'store'])->name('admin_prets_store');




});
