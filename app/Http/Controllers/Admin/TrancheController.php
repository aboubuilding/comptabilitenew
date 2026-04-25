<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annee;
use App\Models\Tranche;

use App\Models\Inscription;


use App\Types\StatutEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrancheController extends Controller
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

        $tranches = Tranche::getListe(null, $annee_id);

        foreach($tranches as $tranche ){
            $data []  = array(

                "id"=>$tranche->id,
                "libelle"=>$tranche->libelle == null ? ' ' :$tranche->libelle,
                "date_butoire"=>$tranche->date_butoire == null ? ' ' :$tranche->cycle->date_butoire,
                 "frais_ecole_id"=>$tranche->frais_ecole_id == null ? ' ' :$tranche->fraisecole->libelle,

                    "type_frais"=>$tranche->type_frais == null ? ' ' :$tranche->type_frais,

                     "taux"=>$tranche->type_frais == null ? ' ' :$tranche->taux,






            );
        }

        return view('admin.Tranche.index')->with(
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
       



        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres',
           



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Tranche::addTranche(

                    $request->libelle,
                    $request->date_butoire,
                    $request->frais_ecole_id,
                    $request->type_frais,
                    $request->taux

                 





                );



                return response()->json(['code'=>1,'msg'=>'Tranche  ajoutée avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string||max:25',
            'cycle_id'=>'required',
            'niveau_id'=>'required',


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres',
           




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Tranche::updateTranche(

                    $request->libelle,
                    $request->date_butoire,
                    $request->frais_ecole_id,
                    $request->type_frais,
                    $request->taux,


                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Tranche modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Tranche
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $tranche = Tranche::rechercheTrancheById($id);


        return response()->json(['code'=>1, 'Tranche'=>$tranche]);


    }









    /**
     * Supprimer   une  Tranche scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Tranche::deleteTranche($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Tranche  supprimée ";
        } else {
            $success = true;
            $message = "Tranche  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }



}
