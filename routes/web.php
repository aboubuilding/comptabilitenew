<?php

use App\Mail\SendMail;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AnneeController;
use Illuminate\Support\Facades\Mail;

require base_path('routes/cantine.php');
require base_path('routes/car.php');


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




// Les routes des interfaces de l admin

Route::get('/login', [App\Http\Controllers\Admin\LoginController::class, 'login'])->name('admin_login');

Route::get('/logout', [App\Http\Controllers\Admin\LoginController::class, 'logout'])->name('admin_logout');

Route::post('/utilisateurs/authenticate', [App\Http\Controllers\Admin\UtilisateurController::class, 'authenticate'])->name('utilisateur_authenticate');

Route::get('/test-mail', function () {
    $mailData = [
        'name' => 'Parent test',
        'message' => 'Ceci est un test de mail via Brevo',
    ];

    Mail::to('adhiraphael@gmail.com')
        ->send(new SendMail("Test Brevo", $mailData));

    return "Mail envoyé (ou erreur visible dans les logs)";
});

Route::middleware(['admin', 'comptable', 'directeur'])->group(function () {

    //-----------------  Tableau de bord admin


    Route::get('/', [\App\Http\Controllers\Admin\TableauController::class, 'tableau'])->name('tableau');
    Route::get('/admin/search/{date}', [\App\Http\Controllers\Admin\TableauController::class, 'search_date'])->name('search_date');




    //-----------------  Inscriptions

    Route::post('change/annee/session', [AnneeController::class, 'changeAnneeInSession'])->name('change.annee.session');

    Route::get('/inscriptions/cycles', [App\Http\Controllers\Admin\InscriptionController::class, 'cycles'])->name('admin_chiffre_cycles');
    Route::get('/inscriptions/cycles/{id}', [\App\Http\Controllers\Admin\InscriptionController::class, 'detailCycle'])->name('admin_chiffres_detail_cycle');
    Route::get('/inscriptions/niveaux/{id}', [\App\Http\Controllers\Admin\InscriptionController::class, 'detailNiveau'])->name('admin_chiffres_detail_niveau');
    Route::get('/inscriptions/classes/{id}', [\App\Http\Controllers\Admin\InscriptionController::class, 'detailClasse'])->name('admin_chiffres_detail_classe');
    Route::get('/inscriptions/eleves/{id}', [\App\Http\Controllers\Admin\InscriptionController::class, 'detailEleve'])->name('admin_chiffres_detail_eleve');
    Route::get('/inscriptions/niveaux/{id}', [\App\Http\Controllers\Admin\InscriptionController::class, 'detailNiveau'])->name('admin_chiffres_detail_niveau');
    Route::get('/inscriptions/niveaux', [App\Http\Controllers\Admin\InscriptionController::class, 'niveaux'])->name('admin_chiffre_niveaux');
    Route::get('/inscriptions/classes', [App\Http\Controllers\Admin\InscriptionController::class, 'classes'])->name('admin_chiffre_classes');
    Route::get('/inscriptions/eleves', [App\Http\Controllers\Admin\InscriptionController::class, 'eleves'])->name('admin_chiffre_eleves');
    Route::get('/inscriptions/charger/{id}', [App\Http\Controllers\Admin\InscriptionController::class, 'charger'])->name('admin_inscriptions_charger');

    Route::get('/inscriptions/paiements/{id}', [App\Http\Controllers\Admin\InscriptionController::class, 'paiement'])->name('admin_inscriptions_paiement');
    Route::post('/inscriptions/remise', [App\Http\Controllers\Admin\InscriptionController::class, 'remise'])->name('admin_inscriptions_remise');
    Route::get('/inscriptions/qrcode', [App\Http\Controllers\Admin\InscriptionController::class, 'qrcode'])->name('admin_inscriptions_rqcode');
    Route::get('/inscriptions/print/{id}', [App\Http\Controllers\Admin\InscriptionController::class, 'print'])->name('admin_inscription_print');
    Route::get('/inscriptions/get-location', [App\Http\Controllers\Admin\InscriptionController::class, 'getLocation'])->name('admin_inscriptions_get_location');
    Route::post('/inscriptions/save-location', [App\Http\Controllers\Admin\InscriptionController::class, 'saveLocation'])->name('admin_inscriptions_save_location');
    Route::get('/inscriptions/locations', [App\Http\Controllers\Admin\InscriptionController::class, 'locations'])->name('admin_inscriptions_locations');
    //----------------- Paiements

    Route::get('/paiements/index', [\App\Http\Controllers\Admin\PaiementController::class, 'index'])->name('admin_paiements_index');
    Route::get('/paiements/details', [\App\Http\Controllers\Admin\PaiementController::class, 'details'])->name('admin_paiements_details');
    Route::get('/paiements/add', [\App\Http\Controllers\Admin\PaiementController::class, 'add'])->name('admin_paiements_add');
    Route::post('/paiements/save', [\App\Http\Controllers\Admin\PaiementController::class, 'store'])->name('admin_paiements_store');
    Route::get('/paiements/detail/{id}', [\App\Http\Controllers\Admin\PaiementController::class, 'detail'])->name('admin_paiements_detail');
    Route::post('/paiements/delete/{id}', [\App\Http\Controllers\Admin\PaiementController::class, 'delete'])->name('admin_paiements_delete');
    Route::get('/paiements/charger/{id}', [App\Http\Controllers\Admin\PaiementController::class, 'charger'])->name('admin_paiements_charger');

    //----------------- Banques

    Route::get('/banques/index', [App\Http\Controllers\Admin\BanqueController::class, 'index'])->name('admin_banque_index');
    Route::get('/banques/add', [App\Http\Controllers\Admin\BanqueController::class, 'add'])->name('admin_banque_add');
    Route::post('/banques/save', [App\Http\Controllers\Admin\BanqueController::class, 'store'])->name('admin_banque_store');
    Route::get('/banques/modifier/{id}', [App\Http\Controllers\Admin\BanqueController::class, 'edit'])->name('admin_banque_edit');
    Route::post('/banques/update/{id}', [App\Http\Controllers\Admin\BanqueController::class, 'update'])->name('admin_banque_update');
    Route::post('/banques/delete/{id}', [App\Http\Controllers\Admin\BanqueController::class, 'delete'])->name('admin_banque_delete');


    //----------------- Cheques

    Route::get('/cheques/index', [App\Http\Controllers\Admin\ChequeController::class, 'index'])->name('admin_cheques_index');
    Route::get('/cheques/modifier/{id}', [App\Http\Controllers\Admin\ChequeController::class, 'edit'])->name('admin_cheque_edit');
    Route::post('/cheques/update/{id}', [App\Http\Controllers\Admin\ChequeController::class, 'update'])->name('admin_cheque_update');
    Route::post('/cheques/delete/{id}', [App\Http\Controllers\Admin\ChequeController::class, 'delete'])->name('admin_cheque_delete');
    Route::post('/cheques/encaisser', [App\Http\Controllers\Admin\ChequeController::class, 'encaisser'])->name('admin_cheque_encaisser');
    Route::post('/cheques/sendFacture/{id}', [App\Http\Controllers\Admin\ChequeController::class, 'sendFacture'])->name('admin_cheque_sendfacture');
    Route::get('/cheques/imprimerFacture/{id}', [App\Http\Controllers\Admin\ChequeController::class, 'imprimerFacture'])->name('admin_cheque_imprimerfacture');
    Route::post('/cheques/supprimer', [App\Http\Controllers\Admin\ChequeController::class, 'supprimer'])->name('admin_cheque_supprimer');

    //----------------- Articles

    Route::get('/articles/index', [App\Http\Controllers\Admin\ArticleController::class, 'index'])->name('admin_artilcles_index');
    Route::get('/articles/add', [App\Http\Controllers\Admin\ArticleController::class, 'add'])->name('admin_articles_add');
    Route::post('/articles/save', [App\Http\Controllers\Admin\ArticleController::class, 'store'])->name('admin_articles_store');
    Route::get('/articles/modifier/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'edit'])->name('admin_articles_edit');
    Route::post('/articles/update/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'update'])->name('admin_articles_update');
    Route::post('/articles/delete/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'delete'])->name('admin_articles_delete');




    //----------------- Activites

    Route::get('/activites/index', [App\Http\Controllers\Admin\ActiviteController::class, 'index'])->name('admin_activites_index');
    Route::get('/activites/add', [App\Http\Controllers\Admin\ActiviteController::class, 'add'])->name('admin_activites_add');
    Route::post('/activites/save', [App\Http\Controllers\Admin\ActiviteController::class, 'store'])->name('admin_activites_store');
    Route::get('/activites/modifier/{id}', [App\Http\Controllers\Admin\ActiviteController::class, 'edit'])->name('admin_activites_edit');
    Route::post('/activites/update/{id}', [App\Http\Controllers\Admin\ActiviteController::class, 'update'])->name('admin_activites_update');
    Route::post('/activites/delete/{id}', [App\Http\Controllers\Admin\ActiviteController::class, 'delete'])->name('admin_activites_delete');


    //----------------- Recouvrements

    Route::get('/recouvrements/scolarite', [App\Http\Controllers\Admin\RecouvrementController::class, 'scolarite'])->name('admin_recouvrement_niveaux');
    Route::get('/recouvrements/cantine', [App\Http\Controllers\Admin\RecouvrementController::class, 'classes'])->name('admin_recouvrement_cantine');
    Route::get('/recouvrements/bus', [App\Http\Controllers\Admin\RecouvrementController::class, 'eleves'])->name('admin_recouvrement_bus');
    Route::get('/recouvrements/examen', [App\Http\Controllers\Admin\RecouvrementController::class, 'eleves'])->name('admin_recouvrement_examen');



    //----------------- Caisses

    Route::get('/caisses/index', [App\Http\Controllers\Admin\CaisseController::class, 'index'])->name('admin_caisse_index');
    Route::get('/caisses/mine', [App\Http\Controllers\Admin\CaisseController::class, 'mine'])->name('admin_caisse_mine');
    Route::get('/caisses/add', [App\Http\Controllers\Admin\CaisseController::class, 'add'])->name('admin_caisse_add');
    Route::post('/caisses/save', [App\Http\Controllers\Admin\CaisseController::class, 'store'])->name('admin_caisse_store');
    Route::get('/caisses/modifier/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'edit'])->name('admin_caisse_edit');
    Route::get('/caisses/detail/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'detail'])->name('admin_caisse_detail');
    Route::post('/caisses/update/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'update'])->name('admin_caisse_update');
    Route::post('/caisses/delete/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'delete'])->name('admin_caisse_delete');
    Route::post('/caisses/cloturer/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'cloturer'])->name('admin_caisse_cloturer');
    Route::get('/caisses/journaltotal/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'journalTotaux'])->name('admin_caisse_journal_total');
    Route::get('/caisses/journaldetails/{id}', [App\Http\Controllers\Admin\CaisseController::class, 'journalDetail'])->name('admin_caisse_journal_detail');



    //----------------- Encaissements

    Route::get('/encaissements/index', [App\Http\Controllers\Admin\EncaissementController::class, 'index'])->name('admin_encaissements_index');
    Route::get('/encaissements/add', [App\Http\Controllers\Admin\EncaissementController::class, 'add'])->name('admin_encaissements__add');
    Route::post('/encaissements/save', [App\Http\Controllers\Admin\EncaissementController::class, 'store'])->name('admin_encaissements__store');
    Route::get('/encaissements/modifier/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'edit'])->name('admin_encaissements_edit');
    Route::get('/encaissements/detail/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'detail'])->name('admin_encaissements_detail');
    Route::post('/encaissements/update/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'update'])->name('admin_encaissements__update');
    Route::post('/encaissements/delete/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'delete'])->name('admin_encaissements__delete');
    Route::get('/encaissements/pdf/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'pdf'])->name('encaissement_pdf');
    Route::post('/encaissements/supprimer/{id}', [App\Http\Controllers\Admin\EncaissementController::class, 'supprimer'])->name('admin_encaissements__supprimer');




    //----------------- Decaissements

    Route::get('/decaissements/index', [App\Http\Controllers\Admin\DecaissementController::class, 'index'])->name('admin_decaissement_index');
    Route::get('/decaissements/mine', [App\Http\Controllers\Admin\DecaissementController::class, 'mine'])->name('admin_decaissement_mine');
    Route::get('/decaissements/add', [App\Http\Controllers\Admin\DecaissementController::class, 'add'])->name('admin_decaissement_add');
    Route::post('/decaissements/save', [App\Http\Controllers\Admin\DecaissementController::class, 'store'])->name('admin_decaissement_store');
    Route::get('/decaissements/modifier/{id}', [App\Http\Controllers\Admin\DecaissementController::class, 'edit'])->name('admin_decaissement_edit');
    Route::post('/decaissements/update/{id}', [App\Http\Controllers\Admin\DecaissementController::class, 'update'])->name('admin_decaissement_update');
    Route::post('/decaissements/delete/{id}', [App\Http\Controllers\Admin\DecaissementController::class, 'delete'])->name('admin_decaissement_delete');


    //----------------- Mouvements

    Route::get('/mouvements/index', [App\Http\Controllers\Admin\MouvementController::class, 'index'])->name('admin_mouvements_index');
    Route::get('/mouvements/modifier/{id}', [App\Http\Controllers\Admin\MouvementController::class, 'edit'])->name('admin_mouvements_edit');
    Route::post('/mouvements/update/{id}', [App\Http\Controllers\Admin\MouvementController::class, 'update'])->name('admin_mouvemnts_update');
    Route::post('/mouvements/delete/{id}', [App\Http\Controllers\Admin\MouvementController::class, 'delete'])->name('admin_mouvemnts_delete');


    //----------------- Utilisateurs

    Route::get('/utilisateurs/index', [App\Http\Controllers\Admin\UtilisateurController::class, 'index'])->name('admin_utilisateur_index');
    Route::get('/utilisateurs/add', [App\Http\Controllers\Admin\UtilisateurController::class, 'add'])->name('admin_utilisateur_add');
    Route::post('/utilisateurs/save', [App\Http\Controllers\Admin\UtilisateurController::class, 'store'])->name('admin_utilisateur_store');
    Route::get('/utilisateurs/modifier/{id}', [App\Http\Controllers\Admin\UtilisateurController::class, 'edit'])->name('admin_utilisateur_edit');
    Route::post('/utilisateurs/update/{id}', [App\Http\Controllers\Admin\UtilisateurController::class, 'update'])->name('admin_utilisateur_update');
    Route::post('/utilisateurs/delete/{id}', [App\Http\Controllers\Admin\UtilisateurController::class, 'delete'])->name('admin_utilisateur_delete');
    Route::get('/utilisateurs/profil', [App\Http\Controllers\Admin\UtilisateurController::class, 'profil'])->name('admin_utilisateur_profil');
    Route::post('/utilisateurs/update-profil/{id}', [App\Http\Controllers\Admin\UtilisateurController::class, 'updateProfil'])->name('admin_utilisateur_update_profil');
    Route::post('/utilisateurs/change-password/', [App\Http\Controllers\Admin\UtilisateurController::class, 'changepassword'])->name('admin_utilisateur_change_password');

    //----------------- Cycles

    Route::get('/cycles/index', [App\Http\Controllers\Admin\CycleController::class, 'index'])->name('admin_cycle_index');
    Route::get('/cycles/add', [App\Http\Controllers\Admin\CycleController::class, 'add'])->name('admin_cycle_add');
    Route::post('/cycles/save', [App\Http\Controllers\Admin\CycleController::class, 'store'])->name('admin_cycle_store');
    Route::get('/cycles/modifier/{id}', [App\Http\Controllers\Admin\CycleController::class, 'edit'])->name('admin_cycle_edit');
    Route::post('/cycles/update/{id}', [App\Http\Controllers\Admin\CycleController::class, 'update'])->name('admin_cycle_update');
    Route::post('/cycles/delete/{id}', [App\Http\Controllers\Admin\CycleController::class, 'delete'])->name('admin_cycle_delete');



    //----------------- Niveaux

    Route::get('/niveaux/index', [App\Http\Controllers\Admin\NiveauController::class, 'index'])->name('admin_niveau_index');
    Route::get('/niveaux/add', [App\Http\Controllers\Admin\NiveauController::class, 'add'])->name('admin_niveau_add');
    Route::post('/niveaux/save', [App\Http\Controllers\Admin\NiveauController::class, 'store'])->name('admin_niveau_store');
    Route::get('/niveaux/modifier/{id}', [App\Http\Controllers\Admin\NiveauController::class, 'edit'])->name('admin_niveau_edit');
    Route::post('/niveaux/update/{id}', [App\Http\Controllers\Admin\NiveauController::class, 'update'])->name('admin_niveau_update');
    Route::post('/niveaux/delete/{id}', [App\Http\Controllers\Admin\NiveauController::class, 'delete'])->name('admin_niveau_delete');




    //----------------- Classes

    Route::get('/classes/index', [App\Http\Controllers\Admin\ClasseController::class, 'index'])->name('admin_classe_index');
    Route::get('/classes/add', [App\Http\Controllers\Admin\ClasseController::class, 'add'])->name('admin_classe_add');
    Route::post('/classes/save', [App\Http\Controllers\Admin\ClasseController::class, 'store'])->name('admin_classe_store');
    Route::get('/classes/modifier/{id}', [App\Http\Controllers\Admin\ClasseController::class, 'edit'])->name('admin_classe_edit');
    Route::post('/classes/update/{id}', [App\Http\Controllers\Admin\ClasseController::class, 'update'])->name('admin_classe_update');
    Route::post('/classes/delete/{id}', [App\Http\Controllers\Admin\ClasseController::class, 'delete'])->name('admin_classe_delete');


    //----------------- Tranches

    Route::get('/tranches/index', [App\Http\Controllers\Admin\TrancheController::class, 'index'])->name('admin_tranches_index');
    Route::get('/tranches/add', [App\Http\Controllers\Admin\TrancheController::class, 'add'])->name('admin_tranches_add');
    Route::post('/tranches/save', [App\Http\Controllers\Admin\TrancheController::class, 'store'])->name('admin_tranches_store');
    Route::get('/tranches/modifier/{id}', [App\Http\Controllers\Admin\TrancheController::class, 'edit'])->name('admin_tranches_edit');
    Route::post('/tranches/update/{id}', [App\Http\Controllers\Admin\TrancheController::class, 'update'])->name('admin_tranches_update');
    Route::post('/tranches/delete/{id}', [App\Http\Controllers\Admin\TrancheController::class, 'delete'])->name('admin_tranches_delete');



    //----------------- Frais ecoles

    Route::get('/fraisecoles/index', [App\Http\Controllers\Admin\FraisEcoleController::class, 'index'])->name('admin_fraisecole_index');
    Route::get('/fraisecoles/add', [App\Http\Controllers\Admin\FraisEcoleController::class, 'add'])->name('admin_fraisecole_add');
    Route::post('/fraisecoles/save', [App\Http\Controllers\Admin\FraisEcoleController::class, 'store'])->name('admin_fraisecole_store');
    Route::get('/fraisecoles/modifier/{id}', [App\Http\Controllers\Admin\FraisEcoleController::class, 'edit'])->name('admin_fraisecole_edit');
    Route::post('/fraisecoles/update/{id}', [App\Http\Controllers\Admin\FraisEcoleController::class, 'update'])->name('admin_fraisecole_update');
    Route::post('/fraisecoles/delete/{id}', [App\Http\Controllers\Admin\FraisEcoleController::class, 'delete'])->name('admin_fraisecole_delete');



    //----------------- Articles vendus

    Route::get('/articles/index', [App\Http\Controllers\Admin\ArticleController::class, 'index'])->name('admin_article_index');
    Route::post('/articles/save', [App\Http\Controllers\Admin\ArticleController::class, 'store'])->name('admin_articles_store');
    Route::get('/articles/modifier/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'edit'])->name('admin_articles_edit');
    Route::post('/articles/update/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'update'])->name('admin_articles_update');
    Route::post('/articles/delete/{id}', [App\Http\Controllers\Admin\ArticleController::class, 'delete'])->name('admin_articles_delete');
});

Route::middleware(['auth'])->group(function () {
    require base_path('routes/gestion_caisse.php');
});