<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cycle;

use App\Models\Eleve;
use App\Models\Classe;


use App\Models\Detail;
use App\Models\Niveau;
use App\Models\Inscription;
use App\Models\ParentEleve;
use App\Types\TypePaiement;
use App\Types\StatutPaiement;
use App\Types\TypeInscription;
use App\Types\StatutValidation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Types\TypeStatus;

class InscriptionController extends Controller
{

    /**
     * Affiche le chaiffres d affaires previsionnel avec
     *
     * @return \Illuminate\Http\Response
     */
    public function cycles()
    {
        $data = [];


        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $cycles = Cycle::getListe();

        foreach ($cycles as $cycle) {

            $scolarite_previsionnel =   Inscription::getScolaritePrevisionnel($annee_id, $cycle->id);
            $cantine_previsionnel =   Inscription::getCantinePrevisionnel($annee_id, $cycle->id);
            $bus_previsionnel =   Inscription::getBusPrevisionnel($annee_id, $cycle->id);
            $paiement_scolarite = (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::FRAIS_SCOLARITE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, $cycle->id);
            $paiement_cantine =  (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::CANTINE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, $cycle->id);
            $paiement_bus = (int)   Detail::getMontantTotal($annee_id, null, TypePaiement::BUS, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, $cycle->id);

            /*  $pourcentage_scolarite = round(($scolarite_previsionnel/$paiement_scolarite),2);
            $pourcentage_cantine = round(($cantine_previsionnel/$paiement_cantine),2);
            $pourcentage_bus  = round(($bus_previsionnel/$paiement_bus),2);
 */



            $data[]  = array(

                "id" => $cycle->id,

                "libelle" => $cycle->libelle == null ? ' ' : $cycle->libelle,

                "scolarite_previsionnel" =>   $scolarite_previsionnel,
                "cantine_previsionnel" =>   $cantine_previsionnel,
                "bus_previsionnel" =>  $bus_previsionnel,


                "total_eleves" =>  Inscription::getTotal($annee_id, null, $cycle->id, null, null,  null, null, StatutValidation::VALIDE),
                "total_anciens" =>  Inscription::getTotal($annee_id, null, $cycle->id, null, null, null, TypeInscription::REINSCRIPTION, StatutValidation::VALIDE),
                "total_nouveau" =>  Inscription::getTotal($annee_id, null, $cycle->id, null, null, null, TypeInscription::INSCRIPTION, StatutValidation::VALIDE),


                "paiement_scolarite" =>  $paiement_scolarite,
                "paiement_cantine" => $paiement_cantine,
                "paiement_bus" => $paiement_bus,


                /*   "pourcentage_scolarite"=>  $pourcentage_scolarite,
                 "pourcentage_cantine"=> $pourcentage_cantine,
                 "pourcentage_bus"=> $pourcentage_bus,

 */




            );
        }

        return view('admin.inscription.cycle')->with(
            [
                'data' => $data,

            ]


        );
    }


    public function getLocation()
    {
        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];

        $inscriptions = Inscription::where('annee_id', $annee_id)
            ->whereNotNull('frais_bus')
            ->where('statut_validation', StatutValidation::VALIDE)
            ->where('etat', TypeStatus::ACTIF)
            ->get();

            // dd(count($inscriptions));

        return view('admin.inscription.geolocalisation')->with(
            [
                'inscriptions' => $inscriptions,

            ]
        );
    }

    public function locations()
    {
        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        // $annee_id = Annee::getAnneeOuverte();
        // $data = Inscription::getListe($annee_id, null, null, null, null, null, null, StatutValidation::VALIDE);
        // $inscriptions = [];
        // foreach ($data as $inscription) {
        //     if ($inscription->lat != null && $inscription->lng != null) {
        //         $inscriptions[] = $inscription;
        //     }
        // }
        // return view('admin.inscription.locations')->with(
        //     [
        //         'inscriptions' => $inscriptions,

        //     ]
        // );
        $inscriptions = [];
        $data = Inscription::whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('annee_id', $annee_id)
            ->where('statut_validation', StatutValidation::VALIDE)
            ->get();

            foreach ($data as $inscription) {
                $inscriptions[] = array(
                    'id' => $inscription->id,
                    'eleve' => $inscription->eleve,
                    'lat' => $inscription->lat,
                    'lng' => $inscription->lng,
                );
            }

        return view('admin.inscription.locations', compact('inscriptions'));
    }


    public function saveLocation(Request $request)
    {
        $inscription = Inscription::rechercheInscriptionById($request->inscription_id);
        $code = 1;
        $msg = '';

        if ($inscription) {
            $inscription->lat = $request->lat;
            $inscription->lng = $request->lng;
            $inscription->save();
            $code = 0;
            $msg = "La position est enregistrée !";
        }

        return response()->json(['code' => $code, 'msg' => $msg]);
    }



    public function qrcode()
    {
        $annee_id = Annee::getAnneeOuverte();
        $is_print = 0;
        $inscriptions = Inscription::getListe($annee_id, null, null, null, null, null, null, StatutValidation::VALIDE, null, null, null, null, null, null, null, $is_print);

        return view('admin.inscription.qrcode')->with(
            [
                'inscriptions' => $inscriptions,

            ]
        );
    }



    public function print($id)
    {
        $inscription = Inscription::rechercheInscriptionById($id);
        $inscription->is_print = 1;
        $inscription->save();

        return view('admin.inscription.print')->with(
            [
                'inscription' => $inscription
            ]

        );
    }






    /**
     * Affiche le chaiffres d affaires previsionnel avec
     *
     * @return \Illuminate\Http\Response
     */
    public function niveaux()
    {
        $data = [];


        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $niveaux = Niveau::getListe();

        foreach ($niveaux as $niveau) {

            $scolarite_previsionnel =   Inscription::getScolaritePrevisionnel($annee_id, null, $niveau->id);
            $cantine_previsionnel =   Inscription::getCantinePrevisionnel($annee_id, null, $niveau->id);
            $bus_previsionnel =   Inscription::getBusPrevisionnel($annee_id, null, $niveau->id);
            $paiement_scolarite = (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::FRAIS_SCOLARITE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, $niveau->id);
            $paiement_cantine =  (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::CANTINE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, $niveau->id);
            $paiement_bus = (int)   Detail::getMontantTotal($annee_id, null, TypePaiement::BUS, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, $niveau->id);

            $data[]  = array(

                "id" => $niveau->id,
                "cycle" => $niveau->cycle_id == null ? ' ' : $niveau->cycle->libelle,

                "libelle" => $niveau->libelle == null ? ' ' : $niveau->libelle,

                "scolarite_previsionnel" =>   $scolarite_previsionnel,
                "cantine_previsionnel" =>   $cantine_previsionnel,
                "bus_previsionnel" =>  $bus_previsionnel,


                "total_eleves" =>  Inscription::getTotal($annee_id, null, null, $niveau->id, null,  null, null, StatutValidation::VALIDE),


                "paiement_scolarite" =>  $paiement_scolarite,
                "paiement_cantine" => $paiement_cantine,
                "paiement_bus" => $paiement_bus,



            );
        }

        return view('admin.inscription.niveau')->with(
            [
                'data' => $data,

            ]


        );
    }




    /**
     * Affiche le chaiffres d affaires previsionnel avec
     *
     * @return \Illuminate\Http\Response
     */
    public function classes()
    {
        $data = [];


        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $classes = Classe::getListe(null, null, $annee_id);

        foreach ($classes as $classe) {

            $scolarite_previsionnel =   Inscription::getScolaritePrevisionnel($annee_id, null, null, $classe->id);
            $cantine_previsionnel =   Inscription::getCantinePrevisionnel($annee_id, null, null, $classe->id);
            $bus_previsionnel =   Inscription::getBusPrevisionnel($annee_id, null, null, $classe->id);
            $paiement_scolarite = (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::FRAIS_SCOLARITE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, null, $classe->id);
            $paiement_cantine =  (int)  Detail::getMontantTotal($annee_id, null, TypePaiement::CANTINE, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, null, $classe->id);
            $paiement_bus = (int)   Detail::getMontantTotal($annee_id, null, TypePaiement::BUS, null, null, StatutPaiement::ENCAISSE, null, null, null, null, null, null, null, null, null, $classe->id);



            $data[]  = array(

                "id" => $classe->id,

                "cycle" => $classe->cycle_id == null ? ' ' : $classe->cycle->libelle,
                "niveau" => $classe->niveau_id == null ? ' ' : $classe->niveau->libelle,

                "libelle" => $classe->libelle == null ? ' ' : $classe->libelle,

                "scolarite_previsionnel" =>   $scolarite_previsionnel,
                "cantine_previsionnel" =>   $cantine_previsionnel,
                "bus_previsionnel" =>  $bus_previsionnel,


                "total_eleves" =>  Inscription::getTotal($annee_id, null, null, $niveau->id, null,  null, null, StatutValidation::VALIDE),


                "paiement_scolarite" =>  $paiement_scolarite,
                "paiement_cantine" => $paiement_cantine,
                "paiement_bus" => $paiement_bus,


            );
        }

        return view('admin.inscription.classe')->with(
            [
                'data' => $data,

            ]


        );
    }



    /**
     * Affiche le chaiffres d affaires previsionnel avec
     *
     * @return \Illuminate\Http\Response
     */
    public function eleves()
    {
        $data = [];


        $session = session()->get('LoginUser');

        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $eleves = Inscription::getListe();

        foreach ($eleves as $eleve) {
            $data[]  = array(

                "id" => $eleve->id,

                "cycle" => $eleve->cycle_id == null ? ' ' : $eleve->cycle->libelle,
                "niveau" => $eleve->niveau_id == null ? ' ' : $eleve->niveau->libelle,
                "nom_eleve" => $eleve->_id == null ? ' ' : $eleve->eleve->nom . ' ' . $eleve->eleve->prenom,


                "total_previsionnel" =>  Inscription::getChiffreAffaire($annee_id, null, $cycle->id),
                "total_reel" => Detail::getMontantTotal(
                    $annee_id,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $cycle->id
                ),



            );
        }

        return view('admin.inscription.eleves')->with(
            [
                'data' => $data,

            ]


        );
    }





    /**
     * Afficher  un  detail d  dun cycle
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detailCycle($id)
    {
        $data = [];
        $cycle = Cycle::rechercheCycleById($id);



        return response()->json(

            [
                'code' => 1,

                'cycle' => $cycle,


            ]
        );
    }




    /**
     * Afficher  un  detail d  dun niveau
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detailNiveau($id)
    {
        $data = [];
        $niveau = Niveau::rechercheNiveauById($id);



        return response()->json(

            [
                'code' => 1,

                'niveau' => $niveau,


            ]
        );
    }




    /**
     * Afficher  un  detail d  dune classe
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detailClasse($id)
    {
        $data = [];
        $classe = Classe::rechercheClasseById($id);



        return response()->json(

            [
                'code' => 1,

                'classe' => $classe,


            ]
        );
    }




    /**
     * Afficher  un  detail d  dune inscription
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detailEleve($id)
    {
        $data = [];
        $inscription = Inscription::rechercheInscriptionById($id);



        return response()->json(

            [
                'code' => 1,

                'inscription' => $inscription,


            ]
        );
    }




    /**
     * Afficher  une inscription
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {

        $inscription = Inscription::rechercheInscriptionById($id);
        $eleve = Eleve::rechercheEleveById($inscription->eleve_id);
        $date_naissance_eleve = $eleve->date_naissance;


        return response()->json([
            'code' => 1,
            'inscription' => $inscription,
            'eleve' => $eleve,
            'date_naissance_eleve' => $date_naissance_eleve,



        ]);
    }




    public function update(Request $request, $id)
    {


        $validator = \Validator::make($request->all(), [

            'nom' => 'required|string|max:25',
            'prenom' => 'required|string|max:25',
            'date_naissance' => 'required',
            'lieu_naissance' => 'required',
            'sexe' => 'required',
            'nationalite_id' => 'required',
            'niveau_id' => 'required',



        ], [
            'nom.required' => 'Le nom  est obligatoire ',
            'nom.max' => 'Le nom ne doit pas depasser 25 caracteres ',
            'nom.string' => 'Le nom  doit etre une chaine de caracteres ',

            'prenom.required' => 'Le prenom  est obligatoire ',
            'prenom.max' => 'Le prenom ne doit pas depasser 25 caracteres ',
            'prenom.string' => 'Le prenom  doit etre une chaine de caracteres ',

            'date_naissance.required' => 'La date de naissance est obligatoire ',
            'lieu_naissance.required' => 'Le lieu de naissance  est obligatoire ',
            'sexe.required' => 'Le sexe est obligatoire',
            'nationalite_id.required' => 'Le choix de la nationalite  est obligatoire  ',

            'niveau_id.required' => 'Le choix du niveau   est obligatoire  ',



        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $inscription = Inscription::rechercheInscriptionById($id);

            if ($inscription) {

                DB::beginTransaction();

                try {

                    // mise a jour des infos de l eleve

                    Eleve::modifierEleve(

                        $request->nom,
                        $request->prenom,
                        $request->date_naissance,
                        $request->lieu_naissance,
                        $request->sexe,
                        $request->nationalite_id,

                        $inscription->eleve_id

                    );

                    // Mise de l inscription

                    Inscription::modifierInscription(


                        $request->classe_id,
                        $request->niveau_id,
                        $id

                    );

                    return response()->json(['code' => 1, 'msg' => 'Inscription modifiée  avec succès ']);
                } catch (\Exception $e) {
                    // En cas d'erreur, on annule la transaction
                    DB::rollBack();

                    // Retourner une réponse d'erreur
                    return response()->json(['message' => 'Erreur lors de la sauvegarde des données : ' . $e->getMessage()], 500);
                }
            } else {


                return response()->json(['code' => 0, 'msg' => 'Inscription  introuvable ']);
            }
        }
    }



    /**
     * Affecter une remise
     *
     * @return \Illuminate\Http\Response
     */
    public function remise(Request $request)
    {

        $inscription = Inscription::rechercheInscriptionById($request->inscription_id);
        $code = 1;
        $msg = '';

        if ($inscription) {
            $inscription_new = Inscription::affecterRemise($request->taux_remise, $inscription->id);
            $code = 0;
            $msg = "La reduction de " . $request->taux_remise . " % est accordé !";
        }

        return response()->json(['code' => $code, 'msg' => $msg]);
    }




    /**
     * Afficher  un Niveau
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $inscription = Inscription::rechercheInscriptionById($id);
        $eleve = Eleve::rechercheEleveById($inscription->eleve_id);
        $parent_principal = ParentEleve::rechercheParentEleveById($inscription->parent_id);

        return view('admin.inscription.detail')->with(
            [
                'inscription' => $inscription,
                'eleve' => $eleve,
                'parent_principal' => $parent_principal,



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
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $inscription  = Inscription::rechercheInscriptionById($id);



        $niveau = Niveau::rechercheNiveauById($inscription->niveau_id);


        $data = [];



        if ($inscription) {

            // Charger les frais de scolarite
            $frais = array(
                array("Frais scolarité",  $inscription->frais_scolarite, TypePaiement::FRAIS_SCOLARITE),
                array("Frais d' assurance",  $inscription->frais_assurance,  TypePaiement::FRAIS_ASSURANCE),



            );

            if ($inscription->type_inscription == TypeInscription::INSCRIPTION) {

                $inscription_ligne = array("Frais d' inscription", $inscription->frais_inscription, TypePaiement::FRAIS_INSCRIPTION);
                $frais[] = $inscription_ligne;
            }

            foreach ($frais as $frai) {
                $montant_deja =  Detail::getMontantTotal($annee_id, null, $frai[2], $id, null, StatutPaiement::ENCAISSE);

                $montant_prevu = (int) $frai[1];

                $reste = $montant_prevu - (int)$montant_deja;
                if ($frai[2] == TypePaiement::FRAIS_SCOLARITE) {
                    $reste = $montant_prevu - $montant_prevu * $inscription->taux_remise / 100 - (int)$montant_deja;
                }
                $data[]  = array(




                    "type_paiement" => $frai[2],
                    "libelle" => $frai[0],
                    "montant_prevu" => $montant_prevu,
                    "montant_deja" => $montant_deja,
                    "reste" => $reste,




                );
            }


            // Charger les données de frais d examens

            $frais_examen  = (float) $inscription->frais_examen;
            $montant_examen_paye  = Detail::getMontantTotal($annee_id, null, TypePaiement::FRAIS_EXAMEN, $id);

            // Charger les données de frais de cantine

            $frais_cantine  = (float) $inscription->frais_cantine;
            $montant_cantine_paye  = Detail::getMontantTotal($annee_id, null, TypePaiement::CANTINE, $id);

            // Charger les données de frais de bus

            $frais_bus  = (float) $inscription->frais_bus;
            $montant_bus_paye  = Detail::getMontantTotal($annee_id, null, TypePaiement::BUS, $id);
        }







        return response()->json([
            'code' => 1,
            'libelle_niveau' => $niveau->libelle,
            'data' => $data,

            // FRAIS d examen
            'frais_examen' => $frais_examen,
            'montant_examen_paye' => $montant_examen_paye,

            // FRAIS de cantine
            'frais_cantine' => $frais_cantine,
            'montant_cantine_paye' => $montant_cantine_paye,

            // FRAIS de bus
            'nbre_mois' => $inscription->nbre_mois,
            'frais_bus' => $frais_bus,
            'montant_bus_paye' => $montant_bus_paye,
            "taux_remise" => $inscription->taux_remise == null ? '0' : $inscription->taux_remise,
        ]);
    }





    /**
     * Charger les paiements  lié à un eleve
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function paiement($id)
    {
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $inscription  = Inscription::rechercheInscriptionById($id);


        $details  = Detail::getListe(null, null,  $id, null, StatutPaiement::ENCAISSE, $annee_id);



        $data = [];




        foreach ($details as $detail) {


            $data[]  = array(



                "id" => $detail->id,
                "reference" => $detail->paiement->reference,
                "libelle" => $detail->libelle,
                "date_paiement" => $detail->paiement->date_paiement,
                "type_paiement" =>  TypePaiement::getTypePaiement($detail->type_paiement),
                "montant" => $detail->montant,
                "payeur" =>  $detail->paiement->payeur,



            );
        }



        return response()->json([
            'code' => 1,

            'data' => $data,


        ]);
    }
}
