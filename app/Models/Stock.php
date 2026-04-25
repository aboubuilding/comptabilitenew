<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Stock extends Model
{
    use HasFactory;

    public function __construct(array $attributes=[])
    {
        parent::__construct($attributes);
        $this->etat=TypeStatus::ACTIF;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [


        'bon_id',
        'produit_id',
        'magasin_id',
        'bon_id',
        'annee_id',
        'quantite',
        'type_mouvement',



        'etat',

    ];



    /**
     * Ajouter une Stock
     *

     * @param  string $bon_id
     * @param  string $produit_id
     * @param  string $magasin_id
     * @param  date $bon_id

     * @param  int $annee_id
     * @param  int $quantite
     * @param  int $type_mouvement



     * @return Stock
     */

    public static function addStock(
        $bon_id,
        $produit_id,
        $magasin_id,


        $annee_id,
        $quantite,
        $type_mouvement




    )
    {
        $stock = new Stock();


        $stock->bon_id = $bon_id;
        $stock->produit_id = $produit_id;
        $stock->magasin_id = $magasin_id;


        $stock->annee_id = $annee_id;

        $stock->quantite = $quantite;
        $stock->type_mouvement = $type_mouvement;

        $stock->created_at = Carbon::now();

        $stock->save();

        return $stock;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Stock
     */

    public static function rechercheStockById($id)
    {

        return   $stock= Stock::findOrFail($id);
    }

    /**
     * Update d'une Stock scolaire

    * @param  string $bon_id
     * @param  string $produit_id
     * @param  string $magasin_id
     * @param  date $bon_id

     * @param  int $annee_id
     * @param  int $quantite
     * @param  int $type_mouvement



     * @param int $id
     * @return  Stock
     */

    public static function updateStock(
         $bon_id,
        $produit_id,
        $magasin_id,

        $annee_id,
        $quantite,
        $type_mouvement,


        $id)
    {


        return   $stock= Stock::findOrFail($id)->update([



            'bon_id' => $bon_id,
            'produit_id' => $produit_id,
            'magasin_id' => $magasin_id,

            'annee_id' => $annee_id,

            'quantite' => $quantite,
            'type_mouvement' => $type_mouvement,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Stock
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteStock($id)
    {

        $stock= Stock::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($stock) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Stocks

     * @param  int $annee_id
     * @param  int $type_mouvement
     * @param  int $bon_id
     * @param  int $magasin_id
     * @param  int $produit_id




     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $type_mouvement = null,
        $bon_id = null,
        $magasin_id = null,
        $produit_id = null



    ) {



        $query =  Stock::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




         if ($type_mouvement != null) {

            $query->where('type_mouvement', '=', $type_mouvement);
        }


        if ($bon_id != null) {

            $query->where('bon_id', '=', $bon_id);
        }


         if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
        }


         if ($magasin_id != null) {

            $query->where('magasin_id', '=', $magasin_id);
        }





        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


        * @param  int $annee_id
     * @param  int $type_mouvement
     * @param  int $bon_id
     * @param  int $magasin_id
     * @param  int $produit_id




     * @return  int $total
     */

    public static function getTotal(


           $annee_id = null,
        $type_mouvement = null,
        $bon_id = null,
        $magasin_id = null,
        $produit_id = null

    ) {

        $query =   DB::table('stocks')


            ->where('stocks.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




         if ($type_mouvement != null) {

            $query->where('type_mouvement', '=', $type_mouvement);
        }


        if ($bon_id != null) {

            $query->where('bon_id', '=', $bon_id);
        }


         if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
        }


         if ($magasin_id != null) {

            $query->where('magasin_id', '=', $magasin_id);
        }





        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



    /**
     * Obtenir une année
     *
     */
    public function annee()
    {


        return $this->belongsTo(Annee::class, 'annee_id');
    }





     /**
     * Obtenir un bon
     *
     */
    public function bon()
    {


        return $this->belongsTo(Bon::class, 'bon_id');
    }





     /**
     * Obtenir un bon
     *
     */
    public function magasin()
    {


        return $this->belongsTo(Magasin::class, 'magasin_id');
    }





     /**
     * Obtenir un produit
     *
     */
    public function produit()
    {


        return $this->belongsTo(Produit::class, 'produit_id');
    }










}
