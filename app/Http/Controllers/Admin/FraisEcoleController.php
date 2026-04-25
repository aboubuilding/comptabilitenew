<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Models\FraisEcole;

use App\Models\Inscription;


use App\Types\StatutEleve;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FraisEcoleController extends Controller
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

        $fraisecoles = FraisEcole::getListe(null, null, null,$annee_id );

        foreach($fraisecoles as $fraisecole ){
            $data []  = array(

                "id"=>$fraisecole->id,
                "libelle"=>$fraisecole->libelle == null ? ' ' :$fraisecole->libelle,
                "montant"=>$fraisecole->montant == null ? ' ' :$fraisecole->montant,
                "type_paiement"=>$fraisecole->type_paiement == null ? ' ' :$fraisecole->type_paiement,
                "type_forfait"=>$fraisecole->type_forfait == null ? ' ' :$fraisecole->type_forfait,




            );
        }

        return view('admin.fraisecole.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string||max:25',
            'type_paiement'=>'required',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres',
            'type_paiement.required'=>'Le choix du type de paiement  est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 FraisEcole::addFraisEcole(

                    $request->libelle,
                    $request->montant,
                    $request->type_paiement,
                    $request->type_forfait,
                    $request->niveau_id,

                     $annee_id





                );



                return response()->json(['code'=>1,'msg'=>'Frais  ajoutée avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string||max:25',
            'type_paiement'=>'required',


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres',
            'type_paiement.required'=>'Le choix du type de paiement  est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                FraisEcole::updateFraisEcole(

                    $request->libelle,
                    $request->montant,
                    $request->type_paiement,
                    $request->type_forfait,
                    $request->niveau_id,

                    $annee_id,


                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Frais modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un FraisEcole
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $fraisecole = FraisEcole::rechercheFraisEcoleById($id);


        return response()->json(['code'=>1, 'fraisecole'=>$fraisecole]);


    }









    /**
     * Supprimer   une  FraisEcole scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = FraisEcole::deleteFraisEcole($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Frais  supprimée ";
        } else {
            $success = true;
            $message = "Frais  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }



}
