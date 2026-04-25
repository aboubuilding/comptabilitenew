<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Models\Caisse;
use App\Models\Detail;
use App\Models\Mouvement;
use App\Models\Paiement;
use App\Models\User;

use App\Types\StatutCaisse;
use App\Types\TypeMouvement;
use App\Types\TypePaiement;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CaisseController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $user = User::rechercheUserById($compte_id);

        $data = [];

        $annee = Annee::rechercheAnneeById($annee_id);



        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }


        $caisses = Caisse::getListe(null, null, $role, $annee_id);

        foreach ($caisses as $caisse) {
            $data[]  = array(

                "id" => $caisse->id,
                "libelle" => $caisse->libelle == null ? ' ' : $caisse->libelle,
                "solde_initial" => $caisse->solde_initial == null ? ' ' : (float) $caisse->solde_initial,
                "solde_final" => $caisse->solde_final == null ? ' ' : (float)$caisse->solde_final,
                "total_encaissement" => Mouvement::getMontantTotal($annee_id, $caisse->id, null, null, TypeMouvement::ENCAISSEMENT),
                "statut" => $caisse->statut == null ? ' ' : $caisse->statut,
                "responsable" => $caisse->responsable_id == null ? ' ' : $caisse->responsable->nom . ' ' . $caisse->responsable->prenom,
                "createur" => $caisse->utilisateur_id == null ? ' ' : $caisse->utilisateur->nom . ' ' . $caisse->utilisateur->prenom,
                


            );
        }

        return view('admin.caisse.index')->with(
            [
                'data' => $data,
                "annee" => $annee,

            ]


        );
    }





    public function store(Request $request)
    {


        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $validator = \Validator::make($request->all(), [
            'libelle' => 'required|string|max:25',




        ], [
            'libelle.required' => 'Le libellé  est obligatoire ',
            'libelle.string' => 'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max' => 'Le libellé  ne peut pas depasser 25 caracteres ',





        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $caisses = Caisse::getListe(StatutCaisse::OUVERT, $compte_id, $compte_id, $annee_id, null);

            if (count($caisses) > 0) {
                return response()->json(['code' => 0, 'error' => ['libelle' => ['Vous avez déjà une caisse ouverte   ! ']]]);
            }

            $caisses = Caisse::getListe(null, $compte_id, $compte_id, $annee_id, $request->libelle);
            $rang = "";
            if (count($caisses) > 0) {
                $rang = "-".count($caisses) + 1;
            }


            Caisse::addCaisse(

                $request->libelle.$rang,
                $request->solde_initial,
                null,
                Carbon::now(),
                null,
                StatutCaisse::OUVERT,
                $compte_id,
                $compte_id,
                $annee_id,




            );



            return response()->json(['code' => 1, 'msg' => 'Caisse  ajoutée avec succès ']);
        }
    }



    public function update(Request $request, $id)
    {

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $validator = \Validator::make($request->all(), [

            'libelle' => 'required|string|max:25',




        ], [
            'libelle.required' => 'Le libellé  est obligatoire ',
            'libelle.string' => 'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max' => 'Le libellé  ne peut pas depasser 25 caracteres ',



        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $caisse = Caisse::rechercheCaisseById($id);

            Caisse::updateCaisse(

                $request->libelle,
                $request->solde_initial,
                $caisse->solde_final,
                $caisse->date_ouverture,
                $caisse->date_cloture,
                $caisse->statut,
                $caisse->utilisateur_id,
                $caisse->responsable_id,
                $caisse->annee_id,

                $id


            );



            return response()->json(['code' => 1, 'msg' => 'Caisse modifiée  avec succès ']);
        }
    }






    /**
     * Afficher  un Caisse
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {

        $caisse = Caisse::rechercheCaisseById($id);


        return response()->json(['code' => 1, 'caisse' => $caisse]);
    }











    /**
     * Supprimer   une  Caisse scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */



    public function delete(Request $request, $id)
    {



        $delete = Caisse::deleteCaisse($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Caisse  supprimée ";
        } else {
            $success = true;
            $message = "Caisse  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }




    /**
     * Afficher  un detail d une caisse
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function detail($id)
    {
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $compte_id = $session['compte_id'];

        $data_encaissements = [];
        $data_decaissements = [];

        $caisse = Caisse::rechercheCaisseById($id);

        $encaissements  = Mouvement::getListe($annee_id, $id, null, null, TypeMouvement::ENCAISSEMENT);


        $decaissements  = Mouvement::getListe($annee_id, $id, null, null, TypeMouvement::DECAISSEMENTS);
        $total_decaissements  = Mouvement::getMontantTotal($annee_id, $id, null, null, TypeMouvement::DECAISSEMENTS);
        $total_encaissements  = Mouvement::getMontantTotal($annee_id, $id, null, null, TypeMouvement::ENCAISSEMENT);


        
        foreach ($encaissements as $encaissement) {


            $data_encaissements[]  = array(

                "id" => $encaissement->id,
                "reference" => $encaissement->paiement->reference == null ? ' ' : $encaissement->paiement->reference,
                "date_operation" => $encaissement->created_at == null ? ' ' : $encaissement->created_at,
                "paiement_id" => $encaissement->paiement_id == null ? ' ' : $encaissement->paiement_id,
                "montant" => $encaissement->montant == 0 ? 0 : (float)$encaissement->montant,
                "etat" => $encaissement->etat == null ? ' ' : $encaissement->etat,

                "date_paiement" => $encaissement->paiement->created_at == null ? ' ' : $encaissement->paiement->created_at,
                "eleve" => $encaissement->paiement->inscription->eleve == null ? ' ' : $encaissement->paiement->inscription->eleve->nom . ' ' . $encaissement->paiement->inscription->eleve->prenom,

            );
        }
        // dd($data_encaissements);

        foreach ($decaissements as $decaissement) {


            $data_decaissements[]  = array(

                "id" => $decaissement->id,
                "reference" => $decaissement->depense->reference == null ? ' ' : $decaissement->depense->reference,
                "date_operation" => $decaissement->created_at == null ? ' ' : $decaissement->created_at,
                "depense_id" => $decaissement->depense_id == null ? ' ' : $decaissement->depense_id,
                "montant" => $decaissement->montant == null ? ' ' : $decaissement->montant,
                "etat" => $decaissement->etat == null ? ' ' : $decaissement->etat,
                "beneficiaire" => $decaissement->beneficiaire == null ? ' ' : $decaissement->beneficaire,


            );
        }



        return view('admin.caisse.detail')->with(
            [

                'caisse' => $caisse,
                'data_encaissements' => $data_encaissements,
                'data_decaissements' => $data_decaissements,
                'encaissements' => $encaissements,
                'decaissements' => $decaissements,
                'total_decaissements' => $total_decaissements,
                'total_encaissements' => $total_encaissements,





            ]
        );
    }





    public function cloturer(Request $request, $id)
    {


        $validator = \Validator::make($request->all(), [

            'solde_final' => 'required'




        ], [
            'solde_final.required' => 'Le solde final  est obligatoire '



        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $caisse = Caisse::rechercheCaisseById($id);

            Caisse::updateCaisse(

                $caisse->libelle,
                $caisse->solde_initial,
                $request->solde_final,
                $caisse->date_ouverture,
                Carbon::now(),
                StatutCaisse::CLOTURE,
                $caisse->utilisateur_id,
                $caisse->responsable_id,
                $caisse->annee_id,

                $id


            );



            return response()->json(['code' => 1, 'msg' => 'Caisse cloturée   avec succès ']);
        }
    }





    /**
     * Generer le journal des totaux
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function journalTotaux($id)
    {

        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];
        $caisse = Caisse::rechercheCaisseById($id);

        $name = "Journal_Total" . $caisse->libelle;

        $encaissements  = Mouvement::getListe($annee_id, $id, null, null, TypeMouvement::ENCAISSEMENT);

        $total_cantine = 0;
        $total_bus = 0;
        $total_scolarite = 0;
        $total_inscription = 0;
        $total_assurance = 0;
        $total_livre = 0;
        $total_produit = 0;
        $total_frais_examen = 0;
        $total_encaissements = 0;


        foreach ($encaissements as  $encaissement) {

            $total_encaissements +=  $encaissement->montant;

            $details  = Detail::getListe($encaissement->paiement_id);

            foreach ($details as $detail) {

                if ($detail->type_paiement == TypePaiement::CANTINE) {
                    $total_cantine +=  $detail->montant;
                }


                if ($detail->type_paiement == TypePaiement::BUS) {
                    $total_bus +=  $detail->montant;
                }


                if ($detail->type_paiement == TypePaiement::FRAIS_SCOLARITE) {
                    $total_scolarite +=  $detail->montant;
                }






                if ($detail->type_paiement == TypePaiement::FRAIS_INSCRIPTION) {
                    $total_inscription +=  $detail->montant;
                }


                if ($detail->type_paiement == TypePaiement::FRAIS_ASSURANCE) {
                    $total_assurance +=  $detail->montant;
                }



                if ($detail->type_paiement == TypePaiement::LIVRE) {
                    $total_livre +=  $detail->montant;
                }


                if ($detail->type_paiement == TypePaiement::PRODUIT) {
                    $total_produit +=  $detail->montant;
                }







                if ($detail->type_paiement == TypePaiement::FRAIS_EXAMEN) {
                    $total_frais_examen +=  $detail->montant;
                }
            }
        }

        $pdf = PDF::loadView(
            'admin.caisse.journaltotal',
            [

                'journaux' => $data,
                'caisse' => $caisse,
                'total_cantine' => $total_cantine,
                'total_bus' => $total_bus,
                'total_scolarite' => $total_scolarite,
                'total_inscription' => $total_inscription,
                'total_assurance' => $total_assurance,
                'total_livre' => $total_livre,
                'total_produit' => $total_produit,
                'total_frais_examen' => $total_frais_examen,
                'total_encaissements' => $total_encaissements,

            ]
        );


        return $pdf->download($name . '.pdf');
    }





    /**
     * Generer le journal des  details
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function journalDetail($id)
    {

        $data = [];
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];
        $caisse = Caisse::rechercheCaisseById($id);

        $name = "Journal_Detail" . $caisse->libelle;

        $encaissements  = Mouvement::getListe($annee_id, $id, null, null, TypeMouvement::ENCAISSEMENT);


        $total_encaissements = 0;


        foreach ($encaissements as  $encaissement) {

            $total_encaissements +=  $encaissement->montant;

            $details  = Detail::getListe($encaissement->paiement_id);


            foreach ($details as $detail) {

                $type_paiement = TypePaiement::getTypePaiement($detail->type_paiement);
                $paiement = Paiement::recherchePaiementById($detail->paiement_id);
                $data[]  = array(

                    "id" => $detail->id,
                    "libelle" => $detail->libelle == null ? ' ' : $detail->libelle,
                    "type_paiement" => $detail->type_paiement == null ? ' ' : $type_paiement,
                    "montant" => $detail->montant == null ? ' ' : $detail->montant,
                    "eleve" => $paiement->inscription->eleve->nom . ' ' . $paiement->inscription->eleve->prenom,

                );
            }
        }

        $pdf = PDF::loadView(
            'admin.caisse.journaldetail',
            [


                'caisse' => $caisse,
                'data' => $data,

                'total_encaissements' => $total_encaissements,

            ]
        );


        return $pdf->download($name . '.pdf');
    }
}
