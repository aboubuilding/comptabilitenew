<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Fournisseur;


use Illuminate\Http\Request;


class FournisseurController extends Controller
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

        $fournisseurs = Fournisseur::getListe();

        foreach($fournisseurs as $fournisseur ){
            $data []  = array(

                "id"=>$fournisseur->id,
                "raison_social"=>$fournisseur->raison_social == null ? ' ' :$fournisseur->raison_social,
                "telephone_contact"=>$fournisseur->telephone_contact == null ? ' ' :$fournisseur->telephone_contact,

                "chiffre_affaire"=>0,


            );
        }

        return view('admin.fournisseur.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'raison_social'=>'required|string|max:25',
            'telephone_contact'=>'required',




        ],[
            'raison_social.required'=>'La raison sociale   est obligatoire ',
            'raison_social.string'=>'La raison sociale  doit etre une chaine de caracteres ',
            'raison_social.max'=>'La raison sociale  ne peut pas depasser 25 caracteres ',
            'telephone_contact.required'=>'Le telephone   est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Fournisseur::addFournisseur(

                    $request->raison_social,
                    $request->nom_contact,
                    $request->telephone_contact,
                    $request->adresse





                );



                return response()->json(['code'=>1,'msg'=>'Fournisseur  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'raison_social'=>'required|string|max:25',
            'telephone_contact'=>'required',


        ],[
            'raison_social.required'=>'La raison sociale   est obligatoire ',
            'raison_social.string'=>'La raison sociale  doit etre une chaine de caracteres ',
            'raison_social.max'=>'La raison sociale  ne peut pas depasser 25 caracteres ',
            'telephone_contact.required'=>'Le telephone   est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Fournisseur::updateFournisseur(

                     $request->raison_social,
                    $request->nom_contact,
                    $request->telephone_contact,
                    $request->adresse,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Fournisseur modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Fournisseur
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $fournisseur = Fournisseur::rechercheFournisseurById($id);


        return response()->json(['code'=>1, 'Fournisseur'=>$fournisseur]);


    }







    /**
     * Supprimer   une  Fournisseur scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Fournisseur::deleteFournisseur($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Fournisseur  supprimée ";
        } else {
            $success = true;
            $message = "Fournisseur  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
