<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Cheque;
use App\Models\Detail;
use App\Models\FraisEcole;
use App\Models\Inscription;
use App\Models\Mouvement;
use App\Models\Paiement;
use App\Models\Souscription;
use App\Models\User;
use App\Types\ModePaiement;
use App\Types\StatutPaiement;
use App\Types\StatutValidation;
use App\Types\TypeMouvement;
use App\Types\TypePaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{


    const NIVEAU_BEPC = 13;
    const NIVEAU_BAC1 = 15;
    const NIVEAU_BAC2 = 16;

    const FRAIS_BEPC = 200000;
    const FRAIS_BAC1 = 500000;
    const FRAIS_BAC2 = 600000;

    /**
     * Affiche la  liste de tous les paiements
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];
        ini_set('memory_limit', '512M');

        //Helper::updateMontantPaiements();


        $user = User::rechercheUserById($compte_id);


        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }

        $debutsemaine = \Illuminate\Support\Carbon::now()->startOfWeek();
        $finsemaine = Carbon::now()->endOfWeek();

        // aujourdhui

        $aujourdhui = Carbon::today();

        $debutmois = \Illuminate\Support\Carbon::now()->startOfMonth();
        $finmois = Carbon::now()->endOfMonth();

        $paiements = Paiement::getListe($annee_id, $role);


        // Totaux paiements
        $total_montant = Mouvement::getMontantTotal($annee_id, null,  null, null, TypeMouvement::ENCAISSEMENT);
        $total__montant_mois = Mouvement::getMontantTotal($annee_id, null,  null, null, TypeMouvement::ENCAISSEMENT, $debutmois, $finmois);
        $total__montant_semaine = Mouvement::getMontantTotal($annee_id, null,  null, null, TypeMouvement::ENCAISSEMENT,  $debutsemaine, $finsemaine);
        $total__montant_jour = Mouvement::getMontantTotal($annee_id, null,  null, null, TypeMouvement::ENCAISSEMENT, $aujourdhui);






        // foreach ($paiements as  $paiement) {
        //     $data[]  = array(
        //         "id" => $paiement->id,
        //         "nom_eleve" => $paiement->inscription_id == null ? ' ' : $paiement->inscription->eleve->nom . ' ' .  $paiement->inscription->eleve->prenom,
        //         "niveau" => $paiement->inscription_id == null ? ' ' : $paiement->inscription->niveau->libelle,
        //         "reference" => $paiement->reference == null ? ' ' : $paiement->reference,
        //         "date_paiement" => $paiement->created_at == null ? ' ' : $paiement->created_at,
        //         "statut_paiement" => $paiement->statut_paiement == null ? ' ' : $paiement->statut_paiement,
        //         "mode_paiement" => $paiement->mode_paiement == null ? ' ' : $paiement->mode_paiement,
        //         "montant" => Detail::getMontantTotal($annee_id, $paiement->id),
        //         "utilisateur" => $paiement->utilisateur_id == null ? ' ' : $paiement->utilisateur->nom . ' ' . $paiement->utilisateur->prenom,
        //     );
        // }

        return view('admin.paiement.index')->with(
            [
                'data' => $paiements,
                'total_montant' => $total_montant,
                'total__montant_mois' => $total__montant_mois,
                'total__montant_semaine' => $total__montant_semaine,
                'total__montant_jour' => $total__montant_jour,
            ]

        );
    }





    /**
     * Affiche la  liste de tous les  details de paiements
     *
     * @return \Illuminate\Http\Response
     */
    public function details()
    {
        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $user = User::rechercheUserById($compte_id);


        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }


        $details = Detail::getListe(null, null, null, null, null, $annee_id, null, null, $role);



        foreach ($details as  $detail) {


            $data[]  = array(

                "id" => $detail->id,
                "nom_eleve" => $detail->inscription_id == null ? ' ' : $detail->inscription->eleve->nom . ' ' .  $detail->inscription->eleve->prenom,
                "niveau" => $detail->inscription_id == null ? ' ' : $detail->inscription->niveau->libelle,
                "reference" => $detail->paiement_id == null ? ' ' : $detail->paiement->reference,
                "date_paiement" => $detail->created_at == null ? ' ' : $detail->created_at,
                "statut_paiement" => $detail->paiement->statut_paiement == null ? ' ' : $detail->paiement->statut_paiement,
                "libelle" => $detail->libelle == null ? ' ' : $detail->libelle,
                "montant" => $detail->montant == null ? ' ' : $detail->montant,
                "paiement_id" => $detail->paiement_id == null ? ' ' : $detail->paiement_id,

                "utilisateur" => $detail->comptable_id == null ? ' ' : $detail->comptable->nom . ' ' . $detail->comptable->prenom,



            );
        }

        return view('admin.paiement.details')->with(
            [
                'data' => $data,






            ]


        );
    }





    /**
     * Affiche la  liste de tous les  details de paiements  du bus
     *
     * @return \Illuminate\Http\Response
     */
    public function parc()
    {
        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $user = User::rechercheUserById($compte_id);


        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }


        $details = Detail::getListe($annee_id, $role);



        foreach ($details as  $detail) {


            $data[]  = array(

                "id" => $detail->id,
                "nom_eleve" => $detail->inscription_id == null ? ' ' : $detail->inscription->eleve->nom . ' ' .  $paiement->inscription->eleve->prenom,
                "niveau" => $detail->inscription_id == null ? ' ' : $detail->inscription->niveau->libelle,
                "reference" => $detail->paiement_id == null ? ' ' : $detail->paiement->reference,
                "date_paiement" => $detail->date_paiement == null ? ' ' : $detail->date_paiement,
                "statut_paiement" => $detail->statut_paiement == null ? ' ' : $detail->statut_paiement,
                "statut_paiement" => $detail->statut_paiement == null ? ' ' : $detail->statut_paiement,



            );
        }

        return view('admin.paiement.parc')->with(
            [
                'data' => $data,






            ]


        );
    }







    /**
     * Affiche la  liste de tous les paiements non encaisses
     *
     * @return \Illuminate\Http\Response
     */
    public function nonencaisses()
    {
        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $user = User::rechercheUserById($compte_id);


        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }


        $paiements = Paiement::getListe($annee_id, $role);




        foreach ($paiements as  $paiement) {


            $data[]  = array(

                "id" => $paiement->id,
                "nom_eleve" => $paiement->inscription_id == null ? ' ' : $paiement->inscription->eleve->nom . ' ' .  $paiement->inscription->eleve->prenom,
                "niveau" => $paiement->inscription_id == null ? ' ' : $paiement->inscription->niveau->libelle,
                "reference" => $paiement->reference == null ? ' ' : $paiement->reference,
                "date_paiement" => $paiement->date_paiement == null ? ' ' : $paiement->date_paiement,
                "statut_paiement" => $paiement->statut_paiement == null ? ' ' : $paiement->statut_paiement,
                "mode_paiement" => $paiement->mode_paiement == null ? ' ' : $paiement->mode_paiement,
                "montant" => Detail::getMontantTotal($annee_id, $paiement->id),
                "utilisateur" => $paiement->utilisateur_id == null ? ' ' : $paiement->utilisateur->nom . ' ' . $paiement->utilisateur->prenom,


            );
        }

        return view('admin.paiement.index')->with(
            [
                'data' => $data,




            ]


        );
    }






    public function store(Request $request)
    {

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $compte_id = $session['compte_id'];

        $validator = \Validator::make($request->all(), [

            'inscription_id' => 'required',
            'payeur' => 'required',
            'mode_paiement' => 'required'

        ], [

            'inscription_id.required' => 'Le choix de l eleve   est obligatoire ',

            'payeur.required' => 'Le payeur  est obligatoire ',

            'mode_paiement.required' => 'Le mode de paiement  est obligatoire ',


        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $ligne_details = $request->ligne_details;

            DB::beginTransaction();
            try {


                // Recuperation de l inscription

                $inscription = Inscription::rechercheInscriptionById($request->inscription_id);


                // Recuperation des produits
                $ligne_produits = $request->ligne_produits;

                // Generation  du numero de paiement
                $numero = Paiement::genererNumero();

                // Enregistrement du paiement

                $paiement = Paiement::addPaiement(

                    $numero. '-' . $compte_id,
                    $request->payeur,
                    null,
                    $request->telephone_payeur,
                    Carbon::now(),
                    StatutPaiement::NON_ENCAISSE,
                    (int) $request->mode_paiement,
                    $request->inscription_id,
                    $compte_id,
                    null,
                    $annee_id,
                    $request->montant_total



                );

                if($request->mode_paiement == ModePaiement::CHEQUE) {

                    // Enregistrement du cheque

                    $cheque = Cheque::addCheque(
                        $request->num_cheque,
                        $request->emetteur_cheque,
                        $annee_id,
                        $paiement->id,
                        $request->date_cheque,
                        StatutPaiement::NON_ENCAISSE,
                        null,
                        $request->banque_cheque,
                        $request->montant_cheque
                    );
                }


                // Ajouter paiement  de produitS

                if ($request->ligne_produits) {

                    foreach ($ligne_produits as $ligne) {

                        Detail::addDetail(
                            $ligne['montant'],
                            "Paiement  de " . $ligne['quantite'] . " quantité(s) de " . $ligne['produit_name'],
                            $paiement->id,

                            TypePaiement::PRODUIT,
                            $paiement->inscription_id,
                            $ligne['produit_id'],
                            StatutPaiement::NON_ENCAISSE,
                            $annee_id,
                            null,
                            null,
                            $compte_id,
                            null,
                            Carbon::today(),
                            null

                        );
                    }
                }

                //Enregistrement des frais de scolarite

                if ($request->ligne_details) {

                    foreach ($ligne_details as $ligne) {


                        if ((float)$ligne['montant']) {

                            $montant = $ligne['montant'];


                            // if (Helper::payer_en_totalite($inscription->id, (float)$ligne['montant']) && $ligne['type_paiement'] == TypePaiement::FRAIS_SCOLARITE) {

                            //     $remise += (int)env('default_remise');

                            //     $deja_paye = Detail::getMontantTotal(null, null, TypePaiement::FRAIS_SCOLARITE, $inscription->id, null, StatutPaiement::ENCAISSE);

                            //     $montant = $service_ecolage->montant - $service_ecolage->montant * $remise / 100 - $deja_paye;

                            //     $inscrip = Inscription::updateInscription(
                            //         $inscription->date_inscription,
                            //         $inscription->eleve_id,
                            //         $inscription->cycle_id,
                            //         $inscription->niveau_id,
                            //         $inscription->last_niveau_id,
                            //         $inscription->classe_id,
                            //         $inscription->espace_id,
                            //         $inscription->type_inscription,
                            //         $inscription->statut_validation,
                            //         $inscription->annee_id,
                            //         $inscription->parent_id,
                            //         $remise,
                            //         $inscription->motif_rejet,
                            //         $inscription->date_validation,
                            //         $inscription->utilisateur_id,
                            //         $inscription->specialite_id_1,
                            //         $inscription->specialite_id_2,
                            //         $inscription->specialite_id_3,
                            //         $inscription->speciaite_abandonne,
                            //         $inscription->bulletin_1,
                            //         $inscription->bulletin_2,
                            //         $inscription->bulletin_3,
                            //         $inscription->dnb,
                            //         $inscription->programme_provenance,
                            //         $inscription->is_cantine,
                            //         $inscription->is_bus,
                            //         $inscription->is_livre,
                            //         $inscription->frais_scolarite,
                            //         $inscription->frais_assurance,
                            //         $inscription->frais_inscription,
                            //         $inscription->frais_cantine,
                            //         $inscription->frais_bus,
                            //         $inscription->frais_livre,
                            //         $inscription->remise_scolarite,

                            //         $inscription->id
                            //     );
                            // }




                            Detail::addDetail(
                                $montant,
                                "Paiement  de " . $ligne['libelle'],
                                $paiement->id,
                                $ligne['type_paiement'],
                                $paiement->inscription_id,
                                null,
                                StatutPaiement::NON_ENCAISSE,
                                $annee_id,
                                null,
                                null,
                                $compte_id,
                                null,
                                Carbon::today(),
                                null




                            );
                        }
                    }
                }




                // Enregistrement de l encaissement de la cantine

                if ($request->montant_cantine) {

                    // Ajout detail du paiement  de la cantine

                    Detail::addDetail(
                        $request->montant_cantine,
                        "Paiement  des frais de cantine  ",
                        $paiement->id,
                        TypePaiement::CANTINE,
                        $paiement->inscription_id,
                        $request->cantine_id,
                        StatutPaiement::NON_ENCAISSE,
                        $annee_id,
                        null,
                        null,
                        $compte_id,
                        null,
                        Carbon::today(),
                        null




                    );

                    // Enregistrement d une nouvelle inscription a la cantine
                    if (!$inscription->frais_cantine) {
                        // Mise à jour de l inscription

                        $inscription->frais_cantine = (int) $request->montant_cantine_annuel;
                        $inscription->save();

                        // Inscription::updateInscriptionSouscription(
                        //     1,

                        //     $inscription->is_bus,
                        //     $inscription->is_livre,
                        //     $inscription->frais_scolarite,
                        //     $inscription->frais_assurance,
                        //     $inscription->frais_inscription,
                        //     (int) $request->montant_cantine_annuel,
                        //     $inscription->frais_bus,
                        //     $inscription->frais_livre,
                        //     $inscription->remise_scolarite,
                        //     $inscription->nbre_mois,
                        //     $inscription->bus_id,
                        //     $inscription->id

                        // );
                    }
                }



                // Enregistrement de l ' encaissement des  frais de bus

                if ($request->montant_bus) {


                    // Ajout detail du paiement de bus

                    Detail::addDetail(
                        $request->montant_bus,
                        "Paiement  des frais de bus   ",
                        $paiement->id,
                        TypePaiement::BUS,
                        $paiement->inscription_id,
                        $request->bus_id,
                        StatutPaiement::NON_ENCAISSE,
                        $annee_id,
                        null,
                        null,
                        $compte_id,
                        null,
                        Carbon::today(),
                        null




                    );

                    // Enregistrement d une nouvelle inscription au bus

                    if (!$inscription->frais_bus) {


                        // Mise à jour de l inscription

                        $inscription->frais_bus = (int)$request->montant_bus_annuel;
                        $inscription->save();

                        // Inscription::updateInscriptionSouscription(
                        //     $inscription->is_cantine,

                        //     1,
                        //     $inscription->is_livre,
                        //     $inscription->frais_scolarite,
                        //     $inscription->frais_assurance,
                        //     $inscription->frais_inscription,
                        //     $inscription->frais_cantine,
                        //     (int)$request->montant_bus_annuel,

                        //     $inscription->frais_livre,
                        //     $inscription->remise_scolarite,
                        //     (int) $request->nbre_mois,
                        //     (int)$request->bus_id,
                        //     $inscription->id,




                        // );
                    }
                }




                // Enregistrement de l encaissement des  frais de livre

                if ($request->montant_livre) {

                    // Ajout detail du du paiement de bus

                    Detail::addDetail(
                        $request->montant_cantine,
                        "Paiement  des frais de livre ",
                        $paiement->id,
                        TypePaiement::BUS,
                        $paiement->inscription_id,
                        $request->livre_id,
                        StatutPaiement::NON_ENCAISSE,
                        $annee_id,
                        null,
                        null,
                        $compte_id,
                        null,
                        Carbon::today(),
                        null




                    );

                    if (!$inscription->frais_livre) {




                        // Mise à jour de l inscription

                        Inscription::updateInscriptionSouscription(
                            $inscription->is_cantine,

                            $inscription->is_bus,
                            1,
                            $inscription->frais_scolarite,
                            $inscription->frais_assurance,
                            $inscription->frais_inscription,
                            $inscription->frais_cantine,
                            $inscription->frais_bus,

                            (int)  $request->montant_livre_annuel,
                            $inscription->remise_scolarite,
                            $inscription->nbre_mois,
                            $inscription->bus_id,
                            $inscription->id,




                        );
                    }
                }


                // Ajout detail du paiement des frais d examens

                if ($request->frais_examen_payer) {


                    Detail::addDetail(
                        $request->frais_examen_payer,
                        "Paiement  des frais d' examen ",
                        $paiement->id,
                        TypePaiement::FRAIS_EXAMEN,
                        $paiement->inscription_id,
                        null,
                        StatutPaiement::NON_ENCAISSE,
                        $annee_id,
                        null,
                        null,
                        $compte_id,
                        null,
                        Carbon::today(),
                        null




                    );
                }

                $paie = Paiement::updatePaiement(
                    $paiement->reference,
                    $paiement->payeur,
                    $paiement->motif_suppression,
                    $paiement->telephone_payeur,
                    $paiement->date_paiement,
                    $paiement->statut_paiement,
                    $paiement->mode_paiement,
                    $paiement->inscription_id,
                    $paiement->utilisateur_id,
                    $paiement->cheque_id,
                    $paiement->annee_id,
                    Detail::getMontantTotal($annee_id, $paiement->id, null, null, null, StatutPaiement::NON_ENCAISSE),

                    $paiement->id

                );





                DB::commit();

                return response()->json(
                    [
                        'code' => 1,
                        'msg' => 'Paiement  ajoutée avec succès ',
                        'paiement_reference' => $numero,
                        'montant' => Detail::getMontantTotal($annee_id, $paiement->id, null, null, null, StatutPaiement::NON_ENCAISSE)




                    ]

                );
            } catch (\Exception $e) {
                // En cas d'erreur, annulez la transaction
                DB::rollback();

                // Gérez l'erreur ou lancez une exception personnalisée
                // throw new CustomException('Une erreur s'est produite');

                return response()->json(
                    [
                        'code' => 0,
                        'msg' => "Une erreur s'est produite !",
                        'data' => $request->all()


                    ]

                );
            }
        }
    }





    /**
     * Afficher  un  detail de paiement
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        $data = [];
        $paiement = Paiement::recherchePaiementById($id);
        $inscription = Inscription::rechercheInscriptionById($paiement->inscription_id);

        $details = Detail::getListe($paiement->id);

        foreach ($details as  $detail) {


            $data[]  = array(

                "id" => $detail->id,
                "libelle" => $detail->libelle == null ? ' ' : $detail->libelle,
                "type_paiement" => $detail->type_paiement == null ? ' ' : $detail->type_paiement,
                "frais_ecole" => $detail->frais_ecole_id == null ? ' ' : $detail->fraisecole->libelle,
                "statut_paiement" => $detail->statut_paiement == null ? ' ' : $detail->statut_paiement,
                "souscription_id" => $detail->souscription_id == null ? ' ' : $detail->souscription_id,
                "montant" => $detail->montant == null ? ' ' : $detail->montant,

            );
        }

        return response()->json(

            [
                'code' => 1,

                'paiement' => $paiement,
                'inscription' => $inscription,
                'data' => $data,


            ]
        );
    }




    /**
     * Afficher   la page d ' ajout de paiement '
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function add()
    {

        $niveau_examen = [13, 15, 16];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $inscriptions = Inscription::getListe(
            $annee_id,
            null,
            null,
            null,
            null,
            null,
            null,
            StatutValidation::VALIDE,
        );

        $eleves = Inscription::getListeExamen($annee_id);

        foreach ($inscriptions as  $eleve) {

            if (!in_array($eleve->niveau_id, $niveau_examen)) {


                Inscription::updateFraisExamen(0, $eleve->id);
            } else {

                if ($eleve->niveau_id == self::NIVEAU_BEPC) {

                    Inscription::updateFraisExamen(self::FRAIS_BEPC, $eleve->id);
                }

                if ($eleve->niveau_id == self::NIVEAU_BAC1) {

                    Inscription::updateFraisExamen(self::FRAIS_BAC1, $eleve->id);
                }


                if ($eleve->niveau_id == self::NIVEAU_BAC2) {

                    Inscription::updateFraisExamen(self::FRAIS_BAC2, $eleve->id);
                }
            }
        }


        $offres_cantine = FraisEcole::getListe(TypePaiement::CANTINE, null, null, $annee_id);
        $offres_bus = FraisEcole::getListe(TypePaiement::BUS, null, null, null);
        $offres_livre = FraisEcole::getListe(TypePaiement::LIVRE, null, null, $annee_id);
        $produits = FraisEcole::getListe(TypePaiement::PRODUIT, null, null, $annee_id);

        return view('admin.paiement.add')->with(
            [

                'offres_cantine' => $offres_cantine,
                'offres_bus' => $offres_bus,
                'offres_livre' => $offres_livre,
                'produits' => $produits,
                'inscriptions' => $inscriptions,


            ]


        );
    }



    /**
     * Charger les frais lié à un eleve
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function charger($id)
    {

        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $paiement  = Paiement::recherchePaiementById($id);

        $inscription = Inscription::rechercheInscriptionById($paiement->inscription_id);


        $remise = 0;

        $payeur = $paiement->payeur;
        $eleve = $paiement->inscription->eleve->nom . ' ' . $paiement->inscription->eleve->prenom;
        $niveau = $paiement->inscription->niveau->libelle;

        $montant = 0;

        $details = Detail::getListe($paiement->id);

        DB::beginTransaction();
        try {

            foreach ($details as  $detail) {

                $montant_detail = $detail->montant;

                if ($detail->type_paiement == TypePaiement::FRAIS_SCOLARITE && $paiement->statut_paiement == StatutPaiement::NON_ENCAISSE) {


                    if (Helper::payer_en_totalite($inscription->id, (float)$detail->montant)) {

                        $remise =  (int)env('default_remise');

                        $service_ecolage = FraisEcole::getPrix(
                            TypePaiement::FRAIS_SCOLARITE,
                            null,
                            $inscription->niveau_id,
                            $inscription->annee_id
                        );



                        $montant_detail -= $service_ecolage->montant * $remise / 100;

                        if ($montant_detail < 0) {
                            $montant_detail = 0;
                            $remise = 0;
                        }

                        $remise += (int)$inscription->taux_remise;

                        Detail::updateDetail(
                            $montant_detail,
                            "Paiement  de " . $detail->libelle,
                            $detail->paiement_id,
                            $detail->type_paiement,
                            $detail->inscription_id,
                            $detail->frais_ecole_id,
                            $detail->statut_paiement,
                            $detail->annee_id,
                            $detail->souscription_id,
                            $detail->caisse_id,
                            $detail->comptable_id,
                            $detail->caissier_id,
                            $detail->date_paiement,
                            $detail->date_encaissement,

                            $detail->id
                        );
                    }
                }

                $montant += $montant_detail;
            }

            Paiement::updatePaiement(
                $paiement->reference,
                $payeur,
                $paiement->motif_suppression,
                $paiement->telephone_payeur,
                $paiement->date_paiement,
                $paiement->statut_paiement,
                $paiement->mode_paiement,
                $paiement->inscription_id,
                $paiement->utilisateur_id,
                $paiement->cheque_id,
                $paiement->annee_id,
                $montant,

                $paiement->id
            );

            $details2 = Detail::getListe($paiement->id);

            foreach ($details2 as  $detail) {


                $type_paiement = TypePaiement::getTypePaiement($detail->type_paiement);

                $data[]  = array(

                    "id" => $detail->id,
                    "libelle" => $detail->libelle == null ? ' ' : $detail->libelle,
                    "type_paiement" => $detail->type_paiement == null ? ' ' : $type_paiement,
                    "frais_ecole" => $detail->frais_ecole_id == null ? ' ' : $detail->fraisecole->libelle,
                    "statut_paiement" => $detail->statut_paiement == null ? ' ' : $detail->statut_paiement,
                    "souscription_id" => $detail->souscription_id == null ? ' ' : $detail->souscription_id,
                    "montant" => $detail->montant == null ? 0 : $detail->montant,


                );
            }

            DB::commit();


            return response()->json([
                'code' => 1,
                'payeur' => $payeur,
                'eleve' => $eleve,
                'niveau' => $niveau,
                'montant' => $montant,
                'data' => $data,
                'paiement' => $paiement,
                'remise' => $remise,

            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, annulez la transaction
            DB::rollback();

            // Gérez l'erreur ou lancez une exception personnalisée
            // throw new CustomException('Une erreur s'est produite');

            return response()->json(
                [
                    'code' => 0,
                    'msg' => "Une erreur s'est produite !",
                    'error' => $e->getMessage()


                ]

            );
        }
    }



    /**
     * Supprimer   une  paiement  scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {



        $delete = Paiement::supprimerPaiement($request->motif, $id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Paiement   supprimé ";
        } else {
            $success = true;
            $message = "Paiement  non trouvé ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }
}
