<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cycle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CycleController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;

        $cycles = Cycle::getListe();

        foreach($cycles as $cycle ){
            $data []  = array(

                "id"=>$cycle->id,
                "libelle"=> $cycle->libelle == null ? ' ' : $cycle->libelle,



            );
        }

        return view('admin.cycle.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){



        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25|unique:cycles',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',

            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres ',
            'libelle.unique'=>'Le libellé  existe déjà ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Cycle::addCycle(

                    $request->libelle,


                );



                return response()->json(['code'=>1,'msg'=>'Cycle  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){


        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25|unique:cycles,libelle,'.$id,


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique'=>'Le libellé   existe déjà ',

             'libelle.max'=>'Le libellé   ne doit pas depasser 25 caracteres  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Cycle::updateCycle(

                    $request->libelle,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Cycle modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Cycle
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $cycle = Cycle::rechercheCycleById($id);


        return response()->json(['code'=>1, 'cycle'=>$cycle]);


    }



    /**
     * Supprimer   une  Cycle scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Cycle::deleteCycle($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Cycle  supprimée ";
        } else {
            $success = true;
            $message = "Cycle  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }









}
