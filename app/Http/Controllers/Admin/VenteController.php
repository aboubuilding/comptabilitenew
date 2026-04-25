<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Models\Paiement;
use App\Models\Vente;

use App\Models\Inscription;


use App\Types\StatutEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VenteController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;
       $session = session()->get('LoginUser');
       $annee_id = $session['annee_id'];

        $ventes = Vente::getListe($annee_id);

        foreach($ventes as $vente ){
            $data []  = array(

                "id"=>$vente->id,
                "eleve"=>$vente->inscription_id == null ? ' ' :$vente->inscription->eleve->nom.' '.$vente->inscription->eleve->prenom,
                "reference"=>$vente->paiement_id == null ? ' ' :$vente->paiement->reference,
                 "libelle"=>$vente->produit_id == null ? ' ' :$vente->produit->libelle,
                 "quantite"=>$vente->quantite == null ? ' ' :$vente->quantite,
                 "date_vente"=>$vente->date_vente == null ? ' ' :$vente->date_vente,



            );
        }

        return view('admin.vente.index')->with(
            [
                'data' => $data,

            ]


        );


    }







    /**
     * Afficher  un Vente
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $vente = Vente::rechercheVenteById($id);
        $paiement = Paiement::rechercheVenteById($vente->paiement_id);
        $inscription = Paiement::rechercheVenteById($vente->inscription_id);


        return response()->json(['code'=>1,
            'vente'=>$vente,
            'paiement'=>$paiement,
            'inscription'=>$inscription,

        ]);


    }





}
