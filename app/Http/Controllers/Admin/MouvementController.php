<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;



use App\Models\Annee;
use App\Models\Caisse;

use App\Models\Detail;
use App\Models\Mouvement;

use App\Models\Inscription;

use App\Models\Paiement;

use App\Models\Souscription;
use App\Models\User;


use App\Types\StatutPaiement;
use App\Types\TypeMouvement;
use App\Types\TypePaiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Excel;

class MouvementController extends Controller
{



    /**
     * Affiche la  liste de tous les Mouvements
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

      

        $mouvements = Mouvement::getListe($annee_id, null, $role, null, TypeMouvement::Mouvement );






        foreach($mouvements as  $mouvement ){


            $data[]  = array(

                "id" => $mouvement->id,
                "libelle" => $mouvement->libelle == null ? ' ' : $mouvement->libelle,
                "date_mouvement" => $mouvement->created_at == null ? ' ' : $mouvement->created_at,
             
                "montant" => $mouvement->montant == null ? ' ' : $mouvement->montant,

                   "type_mouvement" => $mouvement->type_mouvement == null ? ' ' : $mouvement->type_mouvement,

                "caisse" => $mouvement->caisse_id == null ? ' ' : $mouvement->caisse->libelle,
                "utilisateur" => $mouvement->utilisateur_id == null ? ' ' : $mouvement->utilisateur->nom.' '.$mouvement->utilisateur->prenom,


              

            );
        }

        return view('admin.mouvement.index')->with(
            [
                'data' => $data,

              

            ]


        );


    }





   






  











    /**
     * Afficher  un  detail d un Mouvement
     *
     * @param  int $id

     * * @return \Illuminate\Http\JsonResponse
     */
    public function detail ($id)
    {
        $data= [] ;
        $mouvement = Mouvement::rechercheMouvementById($id);
        $caisse  = Caisse::rechercheCaisseById($mouvement->caisse_id);
        $responsable  = User::rechercheUserById($caisse->responsable_id);
       
       

        return response()->json(

            ['code'=>1,

                'mouvement'=>$mouvement,
                'depense'=>$depense,
                'caisse'=>$caisse,
               
                'data'=>$data,


            ]);


    }





}
