<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Chauffeur;


use Illuminate\Http\Request;


class ChauffeurController extends Controller
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

        $chauffeurs = Chauffeur::getListe();

        foreach($chauffeurs as $chauffeur ){
            $data []  = array(

                "id"=>$chauffeur->id,
                "nom_prenom"=>$chauffeur->nom == null ? ' ' :$chauffeur->nom.' '.$chauffeur->prenom,
                "telephone"=>$chauffeur->telephone == null ? ' ' :$chauffeur->telephone,
                "zone"=>'',



            );
        }

        return view('admin.chauffeur.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'nom'=>'required|string|max:25',

            'telephone'=>'required',




        ],[
            'nom.required'=>'Le nom  est obligatoire ',

            'telephone.required'=>'Le telephone  est obligatoire ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Chauffeur::addChauffeur(

                    $request->nom,
                    $request->prenom,
                    $request->telephone,


                     $annee_id




                );



                return response()->json(['code'=>1,'msg'=>'Chauffeur  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'nom'=>'required|string|max:25',

            'telephone'=>'required',


        ],[
             'nom.required'=>'Le nom  est obligatoire ',

            'telephone.required'=>'Le telephone  est obligatoire ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Chauffeur::updateChauffeur(

                   $request->nom,
                    $request->prenom,
                    $request->telephone,


                     $annee_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Chauffeur modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Chauffeur
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $chauffeur = Chauffeur::rechercheChauffeurById($id);


        return response()->json(['code'=>1, 'chauffeur'=>$chauffeur]);


    }







    /**
     * Supprimer   une  Chauffeur scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Chauffeur::deleteChauffeur($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Chauffeur  supprimé ";
        } else {
            $success = true;
            $message = "Chauffeur  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
