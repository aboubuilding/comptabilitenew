<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Magasin;


use Illuminate\Http\Request;


class MagasinController extends Controller
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

        $magasins = Magasin::getListe();

        foreach($magasins as $magasin ){
            $data []  = array(

                "id"=>$magasin->id,
                "libelle"=>$magasin->libelle == null ? ' ' :$magasin->libelle,
                "responsable"=>$magasin->responsable == null ? ' ' :$magasin->responsable,

            );
        }

        return view('admin.magasin.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'libelle'=>'required',





        ],[
            'libelle.required'=>'Le libelle  est obligatoire ',





        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Magasin::addMagasin(

                    $request->libelle,
                    $request->responsable,
                    $request->description




                );



                return response()->json(['code'=>1,'msg'=>'Magasin  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id)
    {

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $validator = \Validator::make($request->all(),[

             'libelle'=>'required',

        ],[
             'libelle.required'=>'Le libelle  est obligatoire ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Magasin::updateMagasin(

                     $request->libelle,
                    $request->responsable,
                    $request->description,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Magasin modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Magasin
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $magasin = Magasin::rechercheMagasinById($id);


        return response()->json(['code'=>1, 'magasin'=>$magasin]);


    }







    /**
     * Supprimer   une  Magasin scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Magasin::deleteMagasin($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Magasin  supprimée ";
        } else {
            $success = true;
            $message = "Magasin  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
