<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Espace extends Model
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


        'nom_famille',
      
        'annee_id',
       

        'etat',

    ];



    /**
     * Ajouter une Espace
     *

     * @param  string $nom_famille
   
     * @param  int $annee_id
    



     * @return Espace
     */

    public static function addEspace(
        $nom_famille,
       
        $annee_id
       

    )
    {
        $espace = new Espace();


        $espace->nom_famille = $nom_famille;
     
        $espace->annee_id = $annee_id;
       
        $espace->created_at = Carbon::now();

        $espace->save();

        return $espace;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Espace
     */

    public static function rechercheEspaceById($id)
    {

        return   $espace= Espace::findOrFail($id);
    }

    /**
     * Update d'une Espace scolaire

      * @param  string $nom_famille
     
     * @param  int $annee_id
    

     * @param int $id
     * @return  Espace
     */

    public static function updateEspace(
         $nom_famille,
      
        $annee_id,
       
       
        $id)
    {


        return   $espace= Espace::findOrFail($id)->update([



            'nom_famille' => $nom_famille,
           
            'annee_id' => $annee_id,
           
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Espace
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteEspace($id)
    {

        $espace= Espace::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($espace) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Espaces

    
     * @param  int $annee_id
   

     *
     * @return  array
     */

    public static function getListe(

      
        $annee_id = null
       
      
        


    ) {

      

        $query =  Espace::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

       

         if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        
       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


   
     * @param  int $annee_id
    

     * @return  int $total
     */

    public static function getTotal(
       
        $annee_id = null
       
       


    ) {

        $query =   DB::table('espaces')


            ->where('espaces.etat', '!=', TypeStatus::SUPPRIME);


      

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
