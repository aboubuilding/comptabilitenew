<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Voiture;


use Illuminate\Http\Request;


class VoitureController extends Controller
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

        $voitures = Voiture::getListe();

        foreach($voitures as $voiture ){
            $data []  = array(

                "id"=>$voiture->id,



                "marque"=>$voiture->marque == null ? ' ' : $voiture->marque,
                "plaque"=>$voiture->plaque == null ? ' ' : $voiture->plaque,
                "nombre_place"=>$voiture->nombre_place == null ? ' ' : $voiture->nombre_place,


            );
        }

        return view('admin.voiture.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'plaque'=>'required',


        ],[
            'plaque.required'=>'La plaque   est obligatoire ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Voiture::addVoiture(

                 
                    $request->marque,
                    $request->plaque,
                    $request->nombre_place,
                     $annee_id




                );



                return response()->json(['code'=>1,'msg'=>'Voiture  ajoutée avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'plaque'=>'required',

        ],[
             'plaque.required'=>'La plaque   est obligatoire ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Voiture::updateVoiture(


                    $request->marque,
                    $request->plaque,
                    $request->nombre_place,
                     $annee_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Voiture modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Voiture
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $voiture = Voiture::rechercheVoitureById($id);


        return response()->json(['code'=>1, 'voiture'=>$voiture]);


    }







    /**
     * Supprimer   une  Voiture scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Voiture::deleteVoiture($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Voiture  supprimée ";
        } else {
            $success = true;
            $message = "Voiture  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
