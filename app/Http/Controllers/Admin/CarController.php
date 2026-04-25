<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Car;
use App\Models\Detail;
use App\Types\TypePaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;


class CarController extends Controller
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

        $cars = Car::getListe($annee_id);

        foreach($cars as $car ){
            $data []  = array(


                "inscription_id"=>$car->inscription_id == null ? ' ' :$car->inscription_id,
                "date_souscription"=>$car->date_souscription == null ? ' ' :$car->date_souscription,
                "montant_annuel_prevu"=>$car->montant_annuel_prevu == null ? ' ' :$car->montant_annuel_prevu,
                "nom_prenom"=>$car->nom_eleve == null ? ' ' :$car->nom_eleve.''.$car->prenom_eleve,

                "type_offre"=>$car->type_offre == null ? ' ' : $car->type_offre,
                "niveau_libelle"=>$car->niveau_libelle == null ? ' ' : $car->niveau_libelle,
                "libelle_cycle"=>$car->libelle_cycle == null ? ' ' : $car->libelle_cycle,
                "total_paye"=> Detail::getMontantTotal($annee_id, null, TypePaiement::Car ,$car->inscription_id ),


            );
        }

        return view('admin.Car.index')->with(
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
            'destination'=>'required',
            'montant_annuel_prevu'=>'required',
            'ligne_id'=>'required',
            'zone_id'=>'required',




        ],[
            'inscription_id.required'=>'Le choix de l eleve   est obligatoire ',

            'destination.required'=>'La destination   est obligatoire  ',
            'montant_annuel_prevu.required'=>'Le montant annel     est obligatoire  ',
            'ligne_id.required'=>'Le choix de la ligne      est obligatoire  ',
            'zone_id.required'=>'Le choix de la zone     est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Car::addCar(

                   Carbon::today(),
                    $request->montant_annuel_prevu,
                    $request->destination,
                    $request->adresse_map,
                     $annee_id,
                    $request->inscription_id,
                    $request->ligne_id,
                    $request->zone_id,



                );



                return response()->json(['code'=>1,'msg'=>'Car  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

           'inscription_id'=>'required',
            'destination'=>'required',
            'montant_annuel_prevu'=>'required',
            'ligne_id'=>'required',
            'zone_id'=>'required',

        ],[
           'inscription_id.required'=>'Le choix de l eleve   est obligatoire ',

            'destination.required'=>'La destination   est obligatoire  ',
            'montant_annuel_prevu.required'=>'Le montant annel     est obligatoire  ',
            'ligne_id.required'=>'Le choix de la ligne      est obligatoire  ',
            'zone_id.required'=>'Le choix de la zone     est obligatoire  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

            $car = Car::rechercheCarById($id);

            Car::updateCar(

                Carbon::today(),
                $request->montant_annuel_prevu,
                $request->destination,
                $request->adresse_map,
                 $annee_id,
                $request->inscription_id,
                $request->ligne_id,
                $request->zone_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Car modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Car
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $car = Car::rechercheCarById($id);


        return response()->json(['code'=>1, 'Car'=>$car]);


    }







    /**
     * Supprimer   une  Car scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Car::deleteCar($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Car  supprimée ";
        } else {
            $success = true;
            $message = "Car  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
