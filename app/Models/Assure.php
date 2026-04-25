<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Assure extends Model
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
        'date_souscription',
        'prelevement_mensuel',
        'assurance_id',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Assure
     *

     * @param  int $personnel_id
     * @param  date $date_souscription
     * @param  int $prelevement_mensuel
     * @param  int $assurance_id
     * @param  int $annee_id



     * @return Assure
     */

    public static function addAssure(
        $personnel_id,
        $date_souscription,
        $prelevement_mensuel,
        $assurance_id,
        $annee_id

    )
    {
        $assure = new Assure();


        $assure->personnel_id = $personnel_id;
        $assure->date_souscription = $date_souscription;
        $assure->prelevement_mensuel = $prelevement_mensuel;
        $assure->assurance_id = $assurance_id;
        $assure->annee_id = $annee_id;


        $assure->created_at = Carbon::now();

        $assure->save();

        return $assure;
    }

    /**
     * Affichage d'une activité scolaire 
     * @param int $id
     * @return  Assure
     */

    public static function rechercheAssureById($id)
    {

        return   $assure = Assure::findOrFail($id);
    }

    /**
     * Update d'une Assure scolaire

   * @param  int $personnel_id
     * @param  date $date_souscription
     * @param  int $prelevement_mensuel
     * @param  int $assurance_id
     * @param  int $annee_id



     * @param int $id
     * @return  Assure
     */

    public static function updateAssure(
       $personnel_id,
        $date_souscription,
        $prelevement_mensuel,
        $assurance_id,
        $annee_id, 

        $id)
    {


        return   $assure = Assure::findOrFail($id)->update([



            'personnel_id' => $personnel_id,
            'date_souscription' => $date_souscription,
            'prelevement_mensuel' => $prelevement_mensuel,
            'assurance_id' => $assurance_id,
            'annee_id' => $annee_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Assure
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteAssure($id)
    {

        $assure = Assure::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($assure) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Assures
     * @param  int $personnel_id
     * @param  int $assurance_id
     * @param  int $annee_id

     *
     * @return  array
     */

    public static function getListe(

        $personnel_id = null,
      
        $assurance_id = null, 
        $annee_id = null

    ) {

      

        $query =  Assure::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($assurance_id != null) {

            $query->where('assurance_id', '=', $assurance_id);
        }

         if ($personnel_id != null) {

            $query->where('personnel_id', '=', $personnel_id);
        }

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


   * @param  int $personnel_id
     * @param  int $assurance_id
     * @param  int $annee_id



     * @return  int $total
     */

    public static function getTotal(
       $personnel_id = null,
      
        $assurance_id = null, 
        $annee_id = null



    ) {

        $query =   DB::table('assures')


            ->where('assures.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('assures.annee_id', '=', $annee_id);
        }


        if ($personnel_id != null) {

            $query->where('assures.personnel_id', '=', $personnel_id);
        }

         if ($assurance_id != null) {

            $query->where('assures.assurance_id', '=', $assurance_id);
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
     * Obtenir un personnel  
     *
     */
    public function personnel()
    {


        return $this->belongsTo(Personnel::class, 'personnel_id');
    }


     /**
     * Obtenir un niveau 
     *
     */
    public function assurance()
    {


        return $this->belongsTo(Niveau::class, 'assurance_id');
    }


}
