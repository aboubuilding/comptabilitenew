<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activite;
use App\Models\Detail;
use App\Models\FraisEcole;
use App\Types\TypePaiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{

    /**
     * Affiche la  liste des  années
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];
        $data= [] ;

        $articles = FraisEcole::getListe(TypePaiement::PRODUIT, null, null, $annee_id);

        foreach($articles as $article ){
            $data []  = array(

                "id"=>$article->id,
                "libelle"=> $article->libelle == null ? ' ' : $article->libelle,
                "montant"=> $article->montant == null ? ' ' : $article->montant,
                "total_article"=> Detail::getMontantTotal(  $annee_id, null, null, null,$article->id ),



            );
        }

        return view('admin.article.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];

        $validator = \Validator::make($request->all(),[
            'libelle'=>'required|string|max:25|unique:frais_ecoles',




        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',

            'libelle.max'=>'Le libellé  ne doit pas depasser 25 caracteres ',
            'libelle.unique'=>'Le libellé  existe déjà ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 FraisEcole::addFraisEcole(

                    $request->libelle,
                    $request->montant,
                    TypePaiement::PRODUIT,

                    null,
                    null,
                    $annee_id

                );



                return response()->json(['code'=>1,'msg'=>'Article  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $compte_id = $session['compte_id'];


        $validator = \Validator::make($request->all(),[

            'libelle'=>'required|string|max:25|unique:frais_ecoles,libelle,'.$id,


        ],[
            'libelle.required'=>'Le libellé  est obligatoire ',
            'libelle.string'=>'Le libellé  doit etre une chaine de caracteres ',
            'libelle.unique'=>'Le libellé   existe déjà ',

             'libelle.max'=>'Le libellé   ne doit pas depasser 25 caracteres  ',


        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                FraisEcole::updateFraisEcole(

                    $request->libelle,
                    $request->montant,
                    TypePaiement::PRODUIT,

                    null,
                    null,
                    $annee_id,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Article  modifiée  avec succès ']);
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

        $article = FraisEcole::rechercheFraisEcoleById($id);


        return response()->json(['code'=>1, 'article'=>$article]);


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



        $delete = FraisEcole::deleteFraisEcole($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Article  supprimée ";
        } else {
            $success = true;
            $message = "Article  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }









}
