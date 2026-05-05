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


    //----------------- Paiements liés a la cantine


    Route::get('/paiements/cantine', [\App\Http\Controllers\Admin_old\PaiementController::class, 'cantine'])->name('admin_paiements_cantine');


     //----------------- Cantines

     Route::get('/cantines/index', [App\Http\Controllers\Admin_old\CantineController::class, 'index'])->name('admin_cantines_index');
     Route::get('/cantines/tableau', [App\Http\Controllers\Admin_old\CantineController::class, 'tableau'])->name('admin_cantines_tableau');
     Route::get('/cantines/add', [App\Http\Controllers\Admin_old\CantineController::class, 'add'])->name('admin_cantines_add');
     Route::post('/cantines/save', [App\Http\Controllers\Admin_old\CantineController::class, 'store'])->name('admin_cantines_store');
     Route::get('/cantines/modifier/{id}', [App\Http\Controllers\Admin_old\CantineController::class, 'edit'])->name('admin_cantines_edit');
     Route::post('/cantines/update/{id}', [App\Http\Controllers\Admin_old\CantineController::class, 'update'])->name('admin_cantines_update');
     Route::post('/cantines/delete/{id}', [App\Http\Controllers\Admin_old\CantineController::class, 'delete'])->name('admin_cantines_delete');


     //----------------- Magasins

    Route::get('/magasins/index', [App\Http\Controllers\Admin_old\MagasinController::class, 'index'])->name('admin_magasins_index');
    Route::get('/magasins/add', [App\Http\Controllers\Admin_old\MagasinController::class, 'add'])->name('admin_magasins_add');
    Route::post('/magasins/save', [App\Http\Controllers\Admin_old\MagasinController::class, 'store'])->name('admin_magasins_store');
    Route::get('/magasins/modifier/{id}', [App\Http\Controllers\Admin_old\MagasinController::class, 'edit'])->name('admin_magasins_edit');
    Route::post('/magasins/update/{id}', [App\Http\Controllers\Admin_old\MagasinController::class, 'update'])->name('admin_magasins_update');
    Route::post('/magasins/delete/{id}', [App\Http\Controllers\Admin_old\MagasinController::class, 'delete'])->name('admin_magasins_delete');



     //----------------- Produits

     Route::get('/produits/index', [App\Http\Controllers\Admin_old\ProduitController::class, 'index'])->name('admin_produits_index');
     Route::get('/produits/add', [App\Http\Controllers\Admin_old\ProduitController::class, 'add'])->name('admin_produits_add');
     Route::post('/produits/save', [App\Http\Controllers\Admin_old\ProduitController::class, 'store'])->name('admin_produits_store');
     Route::get('/produits/modifier/{id}', [App\Http\Controllers\Admin_old\ProduitController::class, 'edit'])->name('admin_produits_edit');
     Route::post('/produits/update/{id}', [App\Http\Controllers\Admin_old\ProduitController::class, 'update'])->name('admin_produits_update');
     Route::post('/produits/delete/{id}', [App\Http\Controllers\Admin_old\ProduitController::class, 'delete'])->name('admin_produits_delete');


     //----------------- Fournisseurs

    Route::get('/fournisseurs/index', [App\Http\Controllers\Admin_old\FournisseurController::class, 'index'])->name('admin_fournisseurs_index');
    Route::get('/fournisseurs/add', [App\Http\Controllers\Admin_old\FournisseurController::class, 'add'])->name('admin_fournisseurs_add');
    Route::post('/fournisseurs/save', [App\Http\Controllers\Admin_old\FournisseurController::class, 'store'])->name('admin_fournisseurs_store');
    Route::get('/fournisseurs/modifier/{id}', [App\Http\Controllers\Admin_old\FournisseurController::class, 'edit'])->name('admin_fournisseurs_edit');
    Route::post('/fournisseurs/update/{id}', [App\Http\Controllers\Admin_old\FournisseurController::class, 'update'])->name('admin_fournisseurs_update');
    Route::post('/fournisseurs/delete/{id}', [App\Http\Controllers\Admin_old\FournisseurController::class, 'delete'])->name('admin_fournisseurs_delete');


           //----------------- Achats

           Route::get('/achats/index', [App\Http\Controllers\Admin_old\AchatController::class, 'index'])->name('admin_achats_index');
           Route::get('/achats/add', [App\Http\Controllers\Admin_old\AchatController::class, 'add'])->name('admin_achats_add');
           Route::post('/achats/save', [App\Http\Controllers\Admin_old\AchatController::class, 'store'])->name('admin_achats_store');
           Route::get('/achats/modifier/{id}', [App\Http\Controllers\Admin_old\AchatController::class, 'edit'])->name('admin_achats_edit');
           Route::post('/achats/update/{id}', [App\Http\Controllers\Admin_old\AchatController::class, 'update'])->name('admin_achats_update');
           Route::post('/achats/delete/{id}', [App\Http\Controllers\Admin_old\AchatController::class, 'delete'])->name('admin_achats_delete');


        //----------------- Stocks

        Route::get('/stocks/index', [App\Http\Controllers\Admin_old\StockController::class, 'index'])->name('admin_stock_index');
        Route::get('/stocks/add', [App\Http\Controllers\Admin_old\StockController::class, 'add'])->name('admin_stok_add');
        Route::post('/stocks/save', [App\Http\Controllers\Admin_old\StockController::class, 'store'])->name('admin_stock_store');
        Route::get('/stocks/modifier/{id}', [App\Http\Controllers\Admin_old\StockController::class, 'edit'])->name('admin_stock_edit');
        Route::post('/stocks/update/{id}', [App\Http\Controllers\Admin_old\StockController::class, 'update'])->name('admin_stock_update');
        Route::post('/stocks/delete/{id}', [App\Http\Controllers\Admin_old\StockController::class, 'delete'])->name('admin_stock_delete');





});
