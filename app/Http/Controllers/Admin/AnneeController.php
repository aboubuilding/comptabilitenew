<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annee;


use App\Types\StatutAnnee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnneeController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [];

        $annees = Annee::getListe();

        foreach ($annees as $annee) {
            $data[]  = array(

                "id" => $annee->id,

                "libelle" => $annee->libelle == null ? ' ' :$annee->libelle,

                "date_rentree" =>$annee->date_rentree == null ? ' ' : $annee->date_rentree,
                 "date_fin" => $annee->date_fin == null ? ' ' :$annee->date_fin,


                "statut_annee" =>$annee->statut_annee == null ? ' ' : $annee->statut_annee,

            );
        }

        return view('admin.annee.index')->with(
            [
                'data' => $data,

            ]


        );
    }
    
    
    public function changeAnneeInSession(Request $request)
    {

        $annee = Annee::rechercheAnneeById($request->annee_id);

        $request->session()->put('LoginUser.annee_id', $annee->id);

        return response()->json(

            [
                'code' => 0,

            ]
        );
    }




    public function store(Request $request)
    {



        $validator = \Validator::make($request->all(), [
            'libelle' => 'required|string|max:20|unique:annees',


        ], [
            'libelle.required' => 'Le libellé  est obligatoire ',

            'libelle.string' => 'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique' => 'Le libellé  existe déjà ',
             'libelle.max' => 'Le libellé  ne peut pas depasser 20 caracteres  ',



        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {
            Annee::addAnnee(

                $request->libelle,
                $request->date_rentree,

                $request->date_fin,
                $request->date_ouverture_inscription,
                $request->date_fermeture_reinscription,

                StatutAnnee::NON_OUVERT,





            );

            return response()->json(['code' => 1, 'msg' => 'Année ajoutée avec succès ']);
        }
    }



    public function update(Request $request, $id)
    {


        $validator = \Validator::make($request->all(), [

             'libelle' => 'required|string|unique:annees',





        ], [
            'libelle.required' => 'Le libellé  est obligatoire ',
            'libelle.string' => 'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique' => 'Le libellé  existe déjà ',



        ]);

        if (!$validator->passes()) {
            return response()->json(['code' => 0, 'error' => $validator->errors()->toArray()]);
        } else {

            $annee = Annee::rechercheAnneeById($id);

                Annee::updateAnnee(

                $request->libelle,
                $request->date_rentree,

                $request->date_fin,
                $request->date_ouverture_inscription,
                $request->date_fermeture_reinscription,

                    $annee->statut_annee,

                $id


            );



            return response()->json(['code' => 1, 'msg' => 'Annee modifiée  avec succès ']);
        }
    }






    /**
     * Afficher  un Annee
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {

        $annee = Annee::rechercheAnneeById($id);


        return response()->json(['code' => 1, 'annee' => $annee]);
    }



    /**
     * Supprimer   une  Annee scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id)
    {



        $delete = Annee::deleteAnnee($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Annee  supprimée ";
        } else {
            $success = true;
            $message = "Annee  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }
}
