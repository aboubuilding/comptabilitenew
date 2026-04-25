<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Depense;




use Illuminate\Http\Request;


class DepenseController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data= [] ;

        $depenses = Depense::getListe();

        foreach($depenses as $depense ){
            $data []  = array(

                "id"=>$depense->id,
                "libelle"=>$depense->libelle == null ? ' ' :$depense->libelle,

                "cycle"=>$depense->cycle_id == null ? ' ' : $depense->cycle->libelle,




            );
        }

        return view('admin.Depense.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){



        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25',
            'cycle_id'=>'required',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé  ne peut pas depasser 25 caracteres ',
            'cycle_id.required'=>'Le choix du cycle est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Depense::addDepense(

                    $request->libelle,
                    $request->cycle_id,
                    $request->description,
                    $request->numero_ordre,




                );



                return response()->json(['code'=>1,'msg'=>'Depense  ajoutée avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25',
            'cycle_id'=>'required',


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.max'=>'Le libellé   ne peut pas depasser 25 caracteres  ',
            'cycle_id.required'=>'Le choix du cycle est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Depense::updateDepense(

                     $request->libelle,
                    $request->cycle_id,
                    $request->description,
                    $request->numero_ordre,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Depense modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Depense
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $depense = Depense::rechercheDepenseById($id);


        return response()->json(['code'=>1, 'Depense'=>$depense]);


    }







    /**
     * Supprimer   une  Depense scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Depense::deleteDepense($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Depense  supprimée ";
        } else {
            $success = true;
            $message = "Depense  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
