<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DetailBon extends Model
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


        'quantite',
        'bon_id',
        'annee_id',
        'produit_id',
        'libelle',
       
       
       

        'etat',

    ];



    /**
     * Ajouter une DetailBon
     *

     * @param  int  $quantite
     * @param  int $bon_id
     * @param  int $annee_id
     * @param  int $produit_id
     * @param  string $libelle
  
    

     * @return DetailBon
     */

    public static function addDetailBon(
        $quantite,
        $bon_id,
        $annee_id,
        $produit_id,
        $libelle
      
       

    )
    {
        $detailbon = new DetailBon();


        $detailbon->quantite = $quantite;
        $detailbon->bon_id = $bon_id;
        $detailbon->annee_id = $annee_id;
        $detailbon->produit_id = $produit_id;
        $detailbon->libelle = $libelle;
       
       
        $detailbon->created_at = Carbon::now();

        $detailbon->save();

        return $detailbon;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  DetailBon
     */

    public static function rechercheDetailBonById($id)
    {

        return   $detailbon= DetailBon::findOrFail($id);
    }

    /**
     * Update d'une DetailBon scolaire

    * @param  int  $quantite
     * @param  int $bon_id
     * @param  int $annee_id
     * @param  int $produit_id
     * @param  string $libelle
    
    

     * @param int $id
     * @return  DetailBon
     */

    public static function updateDetailBon(
         $quantite,
        $bon_id,
        $annee_id,
        $produit_id,
        $libelle,
       
       
        $id)
    {


        return   $detailbon= DetailBon::findOrFail($id)->update([



            'quantite' => $quantite,
            'bon_id' => $bon_id,
            'annee_id' => $annee_id,
            'produit_id' => $produit_id,
            'libelle' => $libelle,
           

           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une DetailBon
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteDetailBon($id)
    {

        $detailbon= DetailBon::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($detailbon) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des DetailBons

     * @param  int $annee_id
     * @param  int $bon_id
     * @param  int $produit_id
  
 


     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $bon_id = null,
        $produit_id = null
      


    ) {

      

        $query =  DetailBon::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        


         if ($bon_id != null) {

            $query->where('bon_id', '=', $bon_id);
        }


        if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
        }

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


      * @param  int $annee_id
     * @param  int $bon_id
     * @param  int $produit_id
    

     * @return  int $total
     */

    public static function getTotal(


         $annee_id = null,
        $bon_id = null,
        $produit_id = null
      


    ) {

        $query =   DB::table('detail_bons')


            ->where('detail_bons.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        


         if ($bon_id != null) {

            $query->where('bon_id', '=', $bon_id);
        }


        if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
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
     * Obtenir un produit
     *
     */
    public function produit()
    {


        return $this->belongsTo(Produit::class, 'produit_id');
    }


    




     /**
     * Obtenir un bon
     *
     */
    public function bon()
    {


        return $this->belongsTo(Bon::class, 'bon_id');
    }


     



  

}
