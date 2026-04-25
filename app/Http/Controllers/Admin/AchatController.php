<?php

namespace App\Http\Controllers\Admin;

use App\Models\Achat;

use App\Models\Produit;
use App\Models\DetailAchat;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use App\Types\StatutLivraison;
use App\Types\StatutReglement;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AchatController extends Controller
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

        $achats = Achat::getListe( $annee_id);

        foreach($achats as $achat ){
            $data []  = array(

                "id"=>$achat->id,
                "date_achat"=>$achat->date_achat == null ? ' ' :$achat->date_achat,
                "nom_acheteur"=>$achat->nom_acheteur == null ? ' ' :$achat->nom_acheteur,
                "reference"=>$achat->reference == null ? ' ' :$achat->reference,
                "fournisseur"=>$achat->fournisseur_id == null ? ' ' :$achat->fournisseur->raison_sociale,

                "statut_paiement"=>$achat->statut_paiement == null ? ' ' : $achat->statut_paiement,
                "statut_livraison"=>$achat->statut_livraison == null ? ' ' : $achat->statut_livraison,


            );
        }

        return view('admin.achat.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'nom_acheteur'=>'required',
            'fournisseur_id'=>'required',




        ],[
            'nom_acheteur.required'=>'Le nom de l acheteur   est obligatoire ',

            'fournisseur_id.required'=>'Le fournisseur    est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


             DB::beginTransaction();
    try {

         // Recuperation des produits
        $ligne_produits = $request->ligne_produits;




        $achat =  Achat::addAchat(

                    $request->date_achat,
                    $request->nom_acheteur,
                    Achat::genererNumero(),
                    $request->bon_commande,
                    $request->commentaire,
                    $request->fournisseur_id,
                     $annee_id,

                    StatutReglement::PAYE,
                     StatutLivraison::NON_LIVRE,




                );


                   // Ajouter paiement  de produitS

                 if ($request->ligne_produits) {

                    foreach ($ligne_produits as $ligne) {

                        DetailAchat::addDetailAchat(


                            $achat->id,


                            $ligne['produit_id'],

                            $annee_id,
                            $ligne['quantite'],
                            $ligne['prix_unitaire'],
                            $ligne['montant'],


                        );
                    }
                }

                DB::commit();

                return response()->json(
                    [
                        'code' => 1,
                        'msg' => 'Achat   ajouté avec succès ',
                        'achat_reference' => $achat->reference,
                        'montant' => DetailAchat::getMontantTotal($annee_id, $achat->id)




                    ]

                );
   } catch (\Exception $e) {
                // En cas d'erreur, annulez la transaction
                DB::rollback();

                // Gérez l'erreur ou lancez une exception personnalisée
                // throw new CustomException('Une erreur s'est produite');

                return response()->json(
                    [
                        'code' => 0,
                        'msg' => "Une erreur s'est produite !",
                        'data' => $request->all()


                    ]

                );
            }
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

            'nom_acheteur'=>'required',
            'fournisseur_id'=>'required',


        ],[
            'nom_acheteur.required'=>'Le nom de l acheteur   est obligatoire ',

            'fournisseur_id.required'=>'Le fournisseur    est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Achat::updateAchat(

                   $request->date_achat,
                    $request->nom_acheteur,
                    $request->reference,
                    $request->bon_commande,
                    $request->commentaire,
                    $request->fournisseur_id,
                     $annee_id,

                    $request->statut_paiement,
                    $request->statut_livraison,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Achat modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Achat
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $achat = Achat::rechercheAchatById($id);


        return response()->json(['code'=>1, 'Achat'=>$achat]);


    }



    /**
     * Afficher   la page d ' ajout d un achat  '
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
 */
    public function add ()
    {

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];

        $fournisseurs = Fournisseur::getListe();


        $produits = Produit::getListe();

       return view('admin.achat.add')->with(
            [


                'produits'=>$produits,
                'fournisseurs'=>$fournisseurs,


            ]


        );


    }







    /**
     * Supprimer   une  Achat scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Achat::deleteAchat($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Achat  supprimée ";
        } else {
            $success = true;
            $message = "Achat  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
