<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Magasin extends Model
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


        'libelle',
        'responsable',
        'description',
     
      
        'etat',

    ];



    /**
     * Ajouter une Magasin
     *

     * @param  string $libelle
     * @param  int $responsable
     * @param  int $description
   



     * @return Magasin
     */

    public static function addMagasin(
        $libelle,
        $responsable,
        $description
       
       

    )
    {
        $magasin = new Magasin();


        $magasin->libelle = $libelle;
        $magasin->responsable = $responsable;
        $magasin->description = $description;
    
       
        $magasin->created_at = Carbon::now();

        $magasin->save();

        return $magasin;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Magasin
     */

    public static function rechercheMagasinById($id)
    {

        return   $magasin= Magasin::findOrFail($id);
    }

    /**
     * Update d'une Magasin scolaire

   * @param  string $libelle
     * @param  int $responsable
     * @param  int $description
 
    

     * @param int $id
     * @return  Magasin
     */

    public static function updateMagasin(
          $libelle,
        $responsable,
        $description,
     
       
        $id)
    {


        return   $magasin= Magasin::findOrFail($id)->update([



            'libelle' => $libelle,
            'responsable' => $responsable,
            'description' => $description,
          
           
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Magasin
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteMagasin($id)
    {

        $magasin= Magasin::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($magasin) {
            return 1;
        }
        return 0;
    }



    /**

    
     * Retourne la liste des Magasins

 
 


     *
     * @return  array
     */

    public static function getListe(

       
        


    ) {

      

        $query =  Magasin::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


   
   
    

     * @return  int $total
     */

    public static function getTotal(
       


    ) {

        $query =   DB::table('magasins')


            ->where('magasins.etat', '!=', TypeStatus::SUPPRIME);



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   
  

}
