<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activite;

use App\Models\Inscription;

use App\Models\Niveau;
use App\Types\Menu;
use App\Types\Sexe;
use App\Types\StatutEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActiviteController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;

        $activites = Activite::getListe();

        foreach($activites as $activite ){
            $data []  = array(

                "id"=>$activite->id,
                "libelle"=> $activite->libelle == null ? ' ' : $activite->libelle,
                "montant"=> $activite->montant == null ? ' ' : $activite->montant,
                "niveau"=> $activite->niveau_id == null ? ' ' : $activite->niveau->libelle,



            );
        }

        return view('admin.activite.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){



        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25|unique:activites',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',

            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres ',
            'libelle.unique'=>'Le libellé  existe déjà ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Activite::addActivite(

                    $request->libelle,
                    $request->description,
                    $request->montant,

                    $request->annee_id,
                    $request->niveau_id,

                );



                return response()->json(['code'=>1,'msg'=>'Activite  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){


        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25|unique:activites,libelle,'.$id,


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique'=>'Le libellé   existe déjà ',

             'libelle.max'=>'Le libellé   ne doit pas depasser 25 caracteres  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Activite::updateActivite(

                   $request->libelle,
                    $request->description,
                    $request->montant,

                    $request->annee_id,
                    $request->niveau_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Activite modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Activite
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $activite = Activite::rechercheActiviteById($id);


        return response()->json(['code'=>1, 'Activite'=>$activite]);


    }



    /**
     * Supprimer   une  Activite scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Activite::deleteActivite($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Activite  supprimée ";
        } else {
            $success = true;
            $message = "Activite  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }









}
