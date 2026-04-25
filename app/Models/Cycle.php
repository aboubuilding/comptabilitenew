<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Cycle extends Model
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
     * Ajouter une Cycle
     *

     * @param  string $libelle
   
    



     * @return Cycle
     */

    public static function addCycle(
        $libelle,
       
       

    )
    {
        $cycle = new Cycle();


        $cycle->libelle = $libelle;
      
       
        $cycle->created_at = Carbon::now();

        $cycle->save();

        return $cycle;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Cycle
     */

    public static function rechercheCycleById($id)
    {

        return   $cycle= Cycle::findOrFail($id);
    }

    /**
     * Update d'une Cycle scolaire

      * @param  string $libelle
    
    

     * @param int $id
     * @return  Cycle
     */

    public static function updateCycle(
         $libelle,
      
       
       
        $id)
    {


        return   $cycle= Cycle::findOrFail($id)->update([



            'libelle' => $libelle,
          
           
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Cycle
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCycle($id)
    {

        $cycle= Cycle::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($cycle) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Cycles

   

     *
     * @return  array
     */

    public static function getListe(

       

    ) {

      

        $query =  Cycle::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        
        
       

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


    
    

     * @return  int $total
     */

    public static function getTotal(
         

    ) {

        $query =   DB::table('Cycles')


            ->where('Cycles.etat', '!=', TypeStatus::SUPPRIME);


      


        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   


  

}
