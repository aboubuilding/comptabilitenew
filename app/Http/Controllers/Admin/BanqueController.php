<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banque;

use App\Models\Inscription;

use App\Models\Niveau;
use App\Types\Menu;
use App\Types\Sexe;
use App\Types\StatutEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BanqueController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;

        $banques = Banque::getListe();

        foreach($banques as $banque ){
            $data []  = array(

                "id"=>$banque->id,
                "libelle"=> $banque->libelle == null ? ' ' : $banque->libelle,
                "total_cheque"=> Cheque::getTotal(),



            );
        }

        return view('admin.banque.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){



        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25|unique:Banques',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',

            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres ',
            'libelle.unique'=>'Le libellé  existe déjà ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Banque::addBanque(

                    $request->libelle,


                );



                return response()->json(['code'=>1,'msg'=>'Banque  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){


        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25|unique:Banques,libelle,'.$id,


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique'=>'Le libellé   existe déjà ',

             'libelle.max'=>'Le libellé   ne doit pas depasser 25 caracteres  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Banque::updateBanque(

                    $request->libelle,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Banque modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Banque
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $banque = Banque::rechercheBanqueById($id);


        return response()->json(['code'=>1, 'Banque'=>$banque]);


    }



    /**
     * Supprimer   une  Banque scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Banque::deleteBanque($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Banque  supprimée ";
        } else {
            $success = true;
            $message = "Banque  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }









}
