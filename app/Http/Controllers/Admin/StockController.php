<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Stock;


use Illuminate\Http\Request;


class StockController extends Controller
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

        $stocks = Stock::getListe();

        foreach($stocks as $stock ){
            $data []  = array(

                "id"=>$stock->id,
                "date_stock"=>$stock->date_stock == null ? ' ' :$stock->date_stock,
                "produit"=>$stock->produit_id == null ? ' ' :$stock->produit->libelle,
                "magasin"=>$stock->magasin_id == null ? ' ' :$stock->magasin->libelle,
                "bon"=>$stock->bon_id == null ? ' ' :$stock->bon->libelle,

                "quantite"=>$stock->quantite == null ? ' ' : $stock->quantite,
                "type_mouvement"=>$stock->type_mouvement == null ? ' ' : $stock->type_mouvement,


            );
        }

        return view('admin.stock.index')->with(
            [
                'data' => $data,

            ]


        );


    }




    public function store(Request $request){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];


        $validator = \Validator::make($request->all(),[
            'produit_id'=>'required',
            'magasin_id'=>'required',




        ],[
            'produit_id.required'=>'Le produit  est obligatoire ',
          
            'magasin_id.required'=>'Le magasin   est obligatoire  ',




        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{


                 Stock::addStock(

                    $request->date_stock,
                    $request->produit_id,
                    $request->magasin_id,
                    $request->bon_id,
                     $annee_id,

                    $request->quantite,
                    $request->type_mouvement,




                );



                return response()->json(['code'=>1,'msg'=>'Stock  ajouté avec succès ']);
            }
        }



    public function update(Request $request, $id){

        $session = session()->get('LoginUser');
        $annee_id = $session['annee_id'];
        $validator = \Validator::make($request->all(),[

             'produit_id'=>'required',
            'magasin_id'=>'required',


        ],[
            'produit_id.required'=>'Le produit  est obligatoire ',
          
            'magasin_id.required'=>'Le magasin   est obligatoire  ',



        ]);

        if(!$validator->passes()){
            return response()->json(['code'=>0,'error'=>$validator->errors()->toArray()]);
        }else{

                Stock::updateStock(

                    $request->date_stock,
                    $request->produit_id,
                    $request->magasin_id,
                    $request->bon_id,
                     $annee_id,

                    $request->quantite,
                    $request->type_mouvement,

                    $id


                );



                return response()->json(['code'=>1,'msg'=>'Stock modifié  avec succès ']);
            }
        }






    /**
     * Afficher  un Stock
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit ($id)
    {

        $stock = Stock::rechercheStockById($id);


        return response()->json(['code'=>1, 'Stock'=>$stock]);


    }







    /**
     * Supprimer   une  Stock scolaire .
     *
     * @param  int  $int
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request,$id)
    {



        $delete = Stock::deleteStock($id);


        // check data deleted or not
        if ($delete) {
            $success = true;
            $message = "Stock  supprimée ";
        } else {
            $success = true;
            $message = "Stock  non trouvée   ";
        }


        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }







}
