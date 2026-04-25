<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Deduction extends Model
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


        'personnel_id',
        'cnss',
        'charges_familiale',
        'forfait_professionnel',
        'annee_id',
       

        'etat',

    ];



    /**
     * Ajouter une Deduction
     *

     * @param  int $personnel_id
     * @param  string $cnss
     * @param  int $charges_familiale
     * @param  int $forfait_professionnel
     * @param  int $annee_id
    



     * @return Deduction
     */

    public static function addDeduction(
        $personnel_id,
        $cnss,
        $charges_familiale,
        $forfait_professionnel,
        $annee_id
       

    )
    {
        $deduction = new Deduction();


        $deduction->personnel_id = $personnel_id;
        $deduction->cnss = $cnss;
        $deduction->charges_familiale = $charges_familiale;
        $deduction->forfait_professionnel = $forfait_professionnel;
        $deduction->annee_id = $annee_id;
       
        $deduction->created_at = Carbon::now();

        $deduction->save();

        return $deduction;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Deduction
     */

    public static function rechercheDeductionById($id)
    {

        return   $deduction= Deduction::findOrFail($id);
    }

    /**
     * Update d'une Deduction scolaire



  * @param  int $personnel_id
     * @param  string $cnss
     * @param  int $charges_familiale
     * @param  int $forfait_professionnel
     * @param  int $annee_id
    


     * @param int $id
     * @return  Deduction
     */

    public static function updateDeduction(
         $personnel_id,
        $cnss,
        $charges_familiale,
        $forfait_professionnel,
        $annee_id, 
       
       
        $id)
    {


        return   $deduction= Deduction::findOrFail($id)->update([



            'personnel_id' => $personnel_id,
            'cnss' => $cnss,
            'charges_familiale' => $charges_familiale,
            'forfait_professionnel' => $forfait_professionnel,
            'annee_id' => $annee_id,
           
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Deduction
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteDeduction($id)
    {

        $deduction= Deduction::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($deduction) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Deductions

     * @param  int $personnel_id
     * @param  int $annee_id
   

     *
     * @return  array
     */

    public static function getListe(

        $espace_id = null,
        $parent_id = null,
        $statut_Deduction = null
      
        


    ) {

      

        $query =  Deduction::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }

         if ($parent_id != null) {

            $query->where('parent_id', '=', $parent_id);
        }

         if ($statut_Deduction != null) {

            $query->where('statut_Deduction', '=', $statut_Deduction);
        }

        
       

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


     * @param  int $espace_id
     * @param  int $parent_id
     * @param  int $statut_Deduction

    

     * @return  int $total
     */

    public static function getTotal(
          $espace_id = null,
        $parent_id = null,
        $statut_Deduction = null
       


    ) {

        $query =   DB::table('Deductions')


            ->where('Deductions.etat', '!=', TypeStatus::SUPPRIME);


       if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }

         if ($parent_id != null) {

            $query->where('parent_id', '=', $parent_id);
        }

         if ($statut_Deduction != null) {

            $query->where('statut_Deduction', '=', $statut_Deduction);
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
     * Obtenir un utilisateur
     *
     */
    public function espace()
    {


        return $this->belongsTo(Espace::class, 'espace_id');
    }



     /**
     * Obtenir un utilisateur
     *
     */
    public function parent()
    {


        return $this->belongsTo(Parent::class, 'parent_id');
    }



  

}
