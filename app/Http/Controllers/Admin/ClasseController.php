<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Classe;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClasseController extends Controller
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

        $classes = Classe::getListe(null, $annee_id);

        foreach($classes as $classe ){
            $data []  = array(

                "id"=>$classe->id,
                "libelle"=>$classe->libelle == null ? ' ' :$classe->libelle,
                "cycle"=>$classe->cycle_id == null ? ' ' :$classe->cycle->libelle,
                 "niveau"=>$classe->niveau_id == null ? ' ' :$classe->niveau->libelle,




            );
        }

        return view('admin.classe.index')->with(
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
            'cycle_id'=>'required',
            'niveau_id'=>'required',



        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres',
            'cycle_id.required'=>'Le choix du cycle est obligatoire  ',

             'niveau_id.required'=>'Le choix du niveau  est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Classe::addClasse(

                    $request->libelle,
                    $request->emplacement,
                    $request->cycle_id,
                    $request->niveau_id,

                     $annee_id,





                );



                return response()->json(['code'=>1,'msg'=>'Classe  ajoutée avec succès ']);
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
            'cycle_id.required'=>'Le choix du cycle est obligatoire  ',

            'niveau_id.required'=>'Le choix du niveau  est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Classe::updateClasse(

                    $request->libelle,
                    $request->emplacement,
                    $request->cycle_id,
                    $request->niveau_id,
                     $annee_id,


                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Classe modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Classe
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $classe = Classe::rechercheClasseById($id);


        return response()->json(['code'=>1, 'classe'=>$classe]);


    }









    /**
     * Supprimer   une  Classe scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Classe::deleteClasse($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Classe  supprimée ";
        } else {
            $success = true;
            $message = "Classe  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }



}
