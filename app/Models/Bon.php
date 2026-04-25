<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Bon extends Model
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


        'date_bon',
        'type',
        'nom_responsable',
        'nom_magasinier',
      
        'annee_id',
      


        'etat',

    ];



    /**
     * Ajouter un Bon 
     *

     * @param  date $date_bon
     * @param  string $type
     * @param  string $nom_responsable
     * @param  int $nom_magasinier

     * @param  int $annee_id
  



     * @return Bon
     */

    public static function addBon(
        $date_bon,
        $type,
        $nom_responsable,
        $nom_magasinier,
     
        $annee_id
      

    )
    {
        $bon = new Bon();


        $bon->date_bon = $date_bon;
        $bon->type = $type;
        $bon->nom_responsable = $nom_responsable;
        $bon->nom_magasinier = $nom_magasinier;
       
        $bon->annee_id = $annee_id;
       


        $bon->created_at = Carbon::now();

        $bon->save();

        return $bon;
    }

    /**
     * Affichage d'un Bon 
     * @param int $id
     * @return  Bon
     */

    public static function rechercheBonById($id)
    {

        return   $bon = Bon::findOrFail($id);
    }

    /**
     * Update d'une Bon scolaire

    * @param  date $date_bon
     * @param  string $type
     * @param  string $nom_responsable
     * @param  int $nom_magasinier
   
     * @param  int $annee_id
   



     * @param int $id
     * @return  Bon
     */

    public static function updateBon(
        $date_bon,
        $type,
        $nom_responsable,
        $nom_magasinier,
      
        $annee_id,
       

        $id)
    {


        return   $bon = Bon::findOrFail($id)->update([



            'date_bon' => $date_bon,
            'type' => $type,
            'nom_responsable' => $nom_responsable,
            'nom_magasinier' => $nom_magasinier,
            
            'annee_id' => $annee_id,
          

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Bon
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteBon($id)
    {

        $bon = Bon::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($bon) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Bons


     * @param  int $annee_id
     * @param  int $fournisseur_id
     * @param  int $statut_paiement
     * @param  int $statut_livraison

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null
      
    


    ) {

      

        $query =  Bon::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

       
       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  Bons  


   * @param  int $annee_id
   

     * @return  int $total
     */

    public static function getTotal(
         $annee_id = null
      
      





    ) {

        $query =   DB::table('bons')


            ->where('bons.etat', '!=', TypeStatus::SUPPRIME);


       if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
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


  


}
