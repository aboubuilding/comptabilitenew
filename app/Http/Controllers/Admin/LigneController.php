<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Ligne;


use Illuminate\Http\Request;


class LigneController extends Controller
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

        $lignes = Ligne::getListe();

        foreach($lignes as $ligne ){
            $data []  = array(

                "id"=>$ligne->id,
                "libelle"=>$ligne->libelle == null ? ' ' :$ligne->libelle,
                "prix_minimal"=>$ligne->prix_minimal == null ? ' ' :$ligne->prix_minimal,
                "prix_plafond"=>$ligne->prix_plafond == null ? ' ' :$ligne->prix_plafond,


            );
        }

        return view('admin.ligne.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25',
            'prix_minimal'=>'required',
            'prix_plafond'=>'required',




        ],[
            'libelle.required'=>'Le libelle  est obligatoire ',
            'prix_minimal.required'=>'Le prix minimal   est obligatoire ',
            'prix_plafond.required'=>'Le prix plafond   est obligatoire ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Ligne::addLigne(

                    $request->libelle,
                    $request->prix_minimal,
                    $request->prix_plafond,



                     $annee_id




                );



                return response()->json(['code'=>1,'msg'=>'Ligne  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

             'libelle'=>'required|string|max:25',
            'prix_minimal'=>'required',
            'prix_plafond'=>'required',


        ],[
             'libelle.required'=>'Le libelle  est obligatoire ',
            'prix_minimal.required'=>'Le prix minimal   est obligatoire ',
            'prix_plafond.required'=>'Le prix plafond   est obligatoire ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Ligne::updateLigne(

                   $request->libelle,
                    $request->prix_minimal,
                    $request->prix_plafond,


                     $annee_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Ligne modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Ligne
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $ligne = Ligne::rechercheLigneById($id);


        return response()->json(['code'=>1, 'ligne'=>$ligne]);


    }







    /**
     * Supprimer   une  Ligne scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Ligne::deleteLigne($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Ligne  supprimée ";
        } else {
            $success = true;
            $message = "Ligne  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
