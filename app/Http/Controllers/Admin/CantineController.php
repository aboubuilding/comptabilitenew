<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Cantine;
use App\Models\Detail;
use App\Types\TypePaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;


class CantineController extends Controller
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

        $cantines = Cantine::getListe($annee_id);

        foreach($cantines as $cantine ){
            $data []  = array(


                "inscription_id"=>$cantine->inscription_id == null ? ' ' :$cantine->inscription_id,
                "date_souscription"=>$cantine->date_souscription == null ? ' ' :$cantine->date_souscription,
                "montant_annuel_prevu"=>$cantine->montant_annuel_prevu == null ? ' ' :$cantine->montant_annuel_prevu,
                "nom_prenom"=>$cantine->nom_eleve == null ? ' ' :$cantine->nom_eleve.''.$cantine->prenom_eleve,

                "type_offre"=>$cantine->type_offre == null ? ' ' : $cantine->type_offre,
                "niveau_libelle"=>$cantine->niveau_libelle == null ? ' ' : $cantine->niveau_libelle,
                "libelle_cycle"=>$cantine->libelle_cycle == null ? ' ' : $cantine->libelle_cycle,
                "total_paye"=> Detail::getMontantTotal($annee_id, null, TypePaiement::CANTINE ,$cantine->inscription_id ),


            );
        }

        return view('admin.cantine.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'inscription_id'=>'required',
            'type_offre'=>'required',
            'montant_annuel_prevu'=>'required',




        ],[
            'inscription_id.required'=>'Le choix de l eleve   est obligatoire ',

            'type_offre.required'=>'Le type d offre    est obligatoire  ',
            'montant_annuel_prevu.required'=>'Le montant annel     est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Cantine::addCantine(

                   Carbon::today(),
                    $request->montant_annuel_prevu,
                    $request->type_offre,
                     $annee_id,
                    $request->inscription_id,


                );



                return response()->json(['code'=>1,'msg'=>'Cantine  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'inscription_id'=>'required',
            'type_offre'=>'required',
            'montant_annuel_prevu'=>'required',

        ],[
            'inscription_id.required'=>'Le choix de l eleve   est obligatoire ',

            'type_offre.required'=>'Le type d offre    est obligatoire  ',
            'montant_annuel_prevu.required'=>'Le montant annel     est obligatoire  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

            $cantine = Cantine::rechercheCantineById($id);

            Cantine::updateCantine(

                    $cantine->date_souscription,
                    $request->montant_annuel_prevu,
                    $request->type_offre,

                     $annee_id,

                    $request->inscription_id,


                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Cantine modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Cantine
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $cantine = Cantine::rechercheCantineById($id);


        return response()->json(['code'=>1, 'cantine'=>$cantine]);


    }







    /**
     * Supprimer   une  Cantine scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Cantine::deleteCantine($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Cantine  supprimée ";
        } else {
            $success = true;
            $message = "Cantine  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
