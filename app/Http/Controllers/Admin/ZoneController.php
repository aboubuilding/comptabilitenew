<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Depense;
use App\Models\DepenseVoiture;
use App\Models\Zone;


use Illuminate\Http\Request;


class ZoneController extends Controller
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

        $zones = Zone::getListe( $annee_id);

        foreach($zones as $zone ){
            $data []  = array(

                "id"=>$zone->id,
                "libelle"=>$zone->libelle == null ? ' ' :$zone->libelle,
                "voiture"=>$zone->voiture_id == null ? ' ' :$zone->voiture->plaque,
                "chauffeur"=>$zone->chauffeur_id == null ? ' ' :$zone->chauffeur->nom.''.$zone->chauffeur->prenom,
                "total_eleve"=> Car::getTotal($annee_id,null,null,null,null,null,$zone->id),
                "total_depense"=> DepenseVoiture::get,
                "chiffre_affaire"=> 0,



            );
        }

        return view('admin.zone.index')->with(
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
            'voiture_id'=>'required',
            'chauffeur_id'=>'required',




        ],[
            'libelle.required'=>'Le libelle  est obligatoire ',
            'voiture_id.required'=>'Le choix de la voiture   est obligatoire ',
            'chauffeur_id.required'=>'Le choix du chauffeur   est obligatoire ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Zone::addZone(

                    $request->libelle,
                    $request->description,
                     $annee_id,
                     $request->chauffeur_id,
                     $request->voiture_id,




                );



                return response()->json(['code'=>1,'msg'=>'Zone  ajoutée avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25',
            'voiture_id'=>'required',
            'chauffeur_id'=>'required',


        ],[
             'libelle.required'=>'Le libelle  est obligatoire ',
            'voiture_id.required'=>'Le choix de la voiture   est obligatoire ',
            'chauffeur_id.required'=>'Le choix du chauffeur   est obligatoire ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Zone::updateZone(

                    $request->libelle,
                    $request->description,
                     $annee_id,
                     $request->chauffeur_id,
                     $request->voiture_id,

,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Zone modifiée  avec succès ']);
            }
        }






    /**
     * Afficher  un Zone
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $zone = Zone::rechercheZoneById($id);


        return response()->json(['code'=>1, 'zone'=>$zone]);


    }







    /**
     * Supprimer   une  Zone scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Zone::deleteZone($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Zone  supprimée ";
        } else {
            $success = true;
            $message = "Zone  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
