<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;




use App\Models\Caisse;

use App\Models\Mouvement;
use App\Models\User;

use App\Types\TypeMouvement;
use Illuminate\Support\Carbon;


class DecaissementController extends Controller
{



    /**
     * Affiche la  liste de tous les Decaissements
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;
        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $user = User::rechercheUserById($compte_id);


        $role = null;
        if ($user->role == \App\Types\Role::COMPTABLE) {

            $role = $compte_id;
        }

        $debutsemaine = \Illuminate\Support\Carbon::now()->startOfWeek()->format('Y-m-d');
        $finsemaine = \Carbon\Carbon::now()->endOfWeek()->format('Y-m-d');

        // aujourdhui

        $aujourdhui = Carbon::today()->format('Y-m-d');

        $debutmois = \Illuminate\Support\Carbon::now()->startOfMonth()->format('Y-m-d');
        $finmois = Carbon::now()->endOfMonth()->format('Y-m-d');

        $decaissements = Mouvement::getListe($annee_id, null, $role, null, TypeMouvement::Decaissement );




        // Totaux paiements
        $total = Mouvement::getTotal($annee_id, null, $role, null, TypeMouvement::Decaissement);
        $total_mois = Mouvement::getTotal($annee_id, null,  $role, null,$debutmois, $finmois);
        $total_semaine = Mouvement::getTotal($annee_id, null,  $role, null, null, $debutsemaine, $finsemaine);
        $total_jour = Mouvement::getTotal($annee_id, null,  $role, null, null,  $aujourdhui);




        foreach($decaissements as  $decaissement ){


            $data[]  = array(

                "id" => $decaissement->id,
                "beneficiaire" => $decaissement->beneficiaire == null ? ' ' : $decaissement->beneficiaire,
                "date_mouvement" => $decaissement->created_at == null ? ' ' : $decaissement->created_at,
                "depense_id" => $decaissement->depense_id == null ? ' ' : $decaissement->depense_id,
                "montant" => $decaissement->montant == null ? ' ' : $decaissement->montant,

                "caisse" => $decaissement->caisse_id == null ? ' ' : $decaissement->caisse->libelle,
                "utilisateur" => $decaissement->utilisateur_id == null ? ' ' : $decaissement->utilisateur->nom.' '.$decaissement->utilisateur->prenom,


                "depense" => $decaissement->depense_id == null ? ' ' : $decaissement->depense->libelle,

            );
        }

        return view('admin.decaissement.index')->with(
            [
                'data' => $data,

                'total' => $total,
                'total_mois' => $total_mois,
                'total_semaine' => $total_semaine,
                'total_jour' => $total_jour,




            ]


        );


    }
















    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function pdf($id)
    {

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $compte_id = $session['compte_id'];

        $decaissement = Mouvement::rechercheMouvementById($id);

        $depense = $decaissement->depense;


        $caisse = Caisse::rechercheCaisseById($decaissement->caisse_id);



        $caissier = User::rechercheUserById($caisse->responsable_id);
        $name = "Decaissement" . $paiement->reference;






        // }else{
        $pdf = PDF::loadView(
            'admin.decaissement.pdf',
            [


                'depense' => $depense,
                'decaissement' => $decaissement,
                "caissier" => $caissier,

            ]
        );
        // }

        return $pdf->download($name . '.pdf');
    }








    /**
     * Afficher  un  detail d un Decaissement
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detail ($id)
    {
        $data= [] ;
        $decaissement = Mouvement::rechercheMouvementById($id);
        $caisse  = Caisse::rechercheCaisseById($decaissement->caisse_id);
        $responsable  = User::rechercheUserById($caisse->responsable_id);

        $depense = Depense::rechercheDepenseById($decaissement->depense_id);



        return response()->json(

            ['code'=>1,

                'decaissement'=>$decaissement,
                'depense'=>$depense,
                'caisse'=>$caisse,

                'data'=>$data,


            ]);


    }





}
