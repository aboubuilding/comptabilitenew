<?php

// routes/domain/logistique.php

use App\Http\Controllers\Domain\Logistique\FournisseurController;
use App\Http\Controllers\Domain\Logistique\AchatController;

use App\Http\Controllers\Domain\Logistique\ProduitController;
use App\Http\Controllers\Domain\Logistique\MagasinController;
use App\Http\Controllers\Domain\Logistique\BonController;
use App\Http\Controllers\Domain\Logistique\StockMouvementController;

use App\Http\Controllers\Domain\Logistique\ClientController;
use App\Http\Controllers\Domain\Logistique\DashboardBoutiqueController;
use App\Http\Controllers\Domain\Logistique\PointDeVenteController;
use App\Http\Controllers\Domain\Logistique\VenteController;
use Illuminate\Support\Facades\Route;

Route::prefix('logistique')->name('logistique.')->group(function () {

    /* ==========================================================
     | 3.1 Fournisseurs
     |========================================================== */
    Route::prefix('fournisseurs')->name('fournisseurs.')->group(function () {
        Route::get('/',                 [FournisseurController::class, 'index'])     ->name('index');
        Route::get('/export',           [FournisseurController::class, 'export'])    ->name('export');
        Route::get('/export-pdf',       [FournisseurController::class, 'exportPdf']) ->name('exportPdf');
        Route::post('/',                [FournisseurController::class, 'store'])     ->name('store');
        Route::get('/{fournisseur}',    [FournisseurController::class, 'show'])      ->name('show');
        Route::put('/{fournisseur}',    [FournisseurController::class, 'update'])    ->name('update');
        Route::delete('/{fournisseur}', [FournisseurController::class, 'destroy'])   ->name('destroy');
    });

    /* ==========================================================
 | 3.2 Achats
 |========================================================== */
Route::prefix('achats')->name('achats.')->group(function () {
    Route::get('/',                   [AchatController::class, 'index'])      ->name('index');
    Route::get('/export',             [AchatController::class, 'export'])     ->name('export');
    Route::post('/',                  [AchatController::class, 'store'])      ->name('store');
    Route::get('/{achat}',            [AchatController::class, 'show'])       ->name('show');
    Route::put('/{achat}',            [AchatController::class, 'update'])     ->name('update');
    Route::post('/{achat}/receptionner', [AchatController::class, 'receptionner'])->name('receptionner');
    Route::post('/{achat}/annuler',   [AchatController::class, 'annuler'])    ->name('annuler');
});


/* ==========================================================
 | 3.3 Produits & Stock
 |========================================================== */

// Produits
Route::prefix('produits')->name('produits.')->group(function () {
    Route::get('/',              [ProduitController::class, 'index'])  ->name('index');
    Route::get('/export',        [ProduitController::class, 'export']) ->name('export');
    Route::post('/',             [ProduitController::class, 'store'])  ->name('store');
   Route::get('/export-pdf', [ProduitController::class, 'exportPdf'])->name('exportPdf');
    Route::get('/{produit}',     [ProduitController::class, 'show'])   ->name('show');
    Route::put('/{produit}',     [ProduitController::class, 'update']) ->name('update');
    Route::delete('/{produit}',  [ProduitController::class, 'destroy'])->name('destroy');
});

// Magasins
Route::prefix('magasins')->name('magasins.')->group(function () {
    Route::get('/',              [MagasinController::class, 'index'])  ->name('index');
    Route::post('/',             [MagasinController::class, 'store'])  ->name('store');
    Route::get('/{magasin}',     [MagasinController::class, 'show'])   ->name('show');
    Route::put('/{magasin}',     [MagasinController::class, 'update']) ->name('update');
    Route::delete('/{magasin}',  [MagasinController::class, 'destroy'])->name('destroy');
});

// Bons (entrée / sortie / transfert)
Route::prefix('bons')->name('bons.')->group(function () {
    Route::get('/',          [BonController::class, 'index'])  ->name('index');
    Route::post('/',         [BonController::class, 'store'])  ->name('store');
    Route::get('/{bon}',     [BonController::class, 'show'])   ->name('show');
});

// Journal des mouvements de stock
Route::prefix('mouvements-stock')->name('mouvements-stock.')->group(function () {
    Route::get('/',          [StockMouvementController::class, 'index'])  ->name('index');
    Route::get('/export',    [StockMouvementController::class, 'export']) ->name('export');
    Route::post('/ajuster',  [StockMouvementController::class, 'ajuster'])->name('ajuster');
});

/* ==========================================================
 | 3.4 Boutique / Point de vente
 |========================================================== */

// Ventes
Route::prefix('ventes')->name('ventes.')->group(function () {
    Route::get('/',                     [VenteController::class, 'index'])       ->name('index');
    Route::get('/export',               [VenteController::class, 'export'])      ->name('export');
    Route::post('/',                    [VenteController::class, 'store'])       ->name('store');
    Route::get('/{vente}',              [VenteController::class, 'show'])        ->name('show');
    Route::get('/{vente}/ticket',       [VenteController::class, 'ticket'])      ->name('ticket');
    Route::post('/{vente}/annuler',     [VenteController::class, 'annuler'])     ->name('annuler');
    Route::post('/{vente}/marquer-payee',[VenteController::class, 'marquerPayee'])->name('marquerPayee');
});

// Clients externes
Route::prefix('clients')->name('clients.')->group(function () {
    Route::get('/',              [ClientController::class, 'index'])      ->name('index');
    Route::get('/rechercher',    [ClientController::class, 'rechercher']) ->name('rechercher');
    Route::get('/export',        [ClientController::class, 'export'])     ->name('export');
    Route::post('/',             [ClientController::class, 'store'])      ->name('store');
    Route::put('/{client}',      [ClientController::class, 'update'])     ->name('update');
    Route::delete('/{client}',   [ClientController::class, 'destroy'])    ->name('destroy');
});

Route::prefix('pos')->name('pos.')->group(function () {
    Route::get('/',                 [PointDeVenteController::class, 'index'])           ->name('index');
    Route::get('/rechercher',       [PointDeVenteController::class, 'rechercherProduit'])->name('rechercher');
    Route::get('/ventes-du-jour',   [PointDeVenteController::class, 'ventesDuJour'])    ->name('ventesDuJour');
    Route::post('/vendre',          [PointDeVenteController::class, 'store'])           ->name('vendre');
});

// Reversement des ventes de la boutique vers une caisse
Route::prefix('reversements')->name('ventes.reversement.')->group(function () {
    Route::get('/',  [\App\Http\Controllers\Domain\Logistique\ReversementVenteController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Domain\Logistique\ReversementVenteController::class, 'store'])->name('store');
});

// Tableau de bord du stock
Route::get('/stock/dashboard', [\App\Http\Controllers\Domain\Logistique\StockDashboardController::class, 'index'])->name('stock.dashboard');

Route::get('/dashboard', [DashboardBoutiqueController::class, 'index'])->name('dashboard');
});