<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Marque extends Model
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
       
       

        'etat',

    ];



    /**
     * Ajouter une Marque
     *

     * @param  string $libelle
    
    



     * @return Marque
     */

    public static function addMarque(
        $libelle,
        $frais_ecole_id,
        $chauffeur_id,
        $voiture_id,
        $annee_id
       

    )
    {
        $Marque = new Marque();


        $Marque->libelle = $libelle;
      
       
        $Marque->created_at = Carbon::now();

        $Marque->save();

        return $Marque;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Marque
     */

    public static function rechercheMarqueById($id)
    {

        return   $Marque= Marque::findOrFail($id);
    }

    /**
     * Update d'une Marque scolaire

      * @param  string $libelle
    
    

     * @param int $id
     * @return  Marque
     */

    public static function updateMarque(
         $libelle,
       
       
       
        $id)
    {


        return   $Marque= Marque::findOrFail($id)->update([



            'libelle' => $libelle,
           
           
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Marque
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteMarque($id)
    {

        $Marque= Marque::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($Marque) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Marques

   
   
 


     *
     * @return  array
     */

    public static function getListe(

       
     
      
        


    ) {

      

        $query =  Marque::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        

      
       

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


  
   
    

     * @return  int $total
     */

    public static function getTotal(
          
       


    ) {

        $query =   DB::table('marques')


            ->where('marques.etat', '!=', TypeStatus::SUPPRIME);


      
        


        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   

   

}
