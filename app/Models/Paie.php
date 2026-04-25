<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Paie extends Model
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


        'employe_id',
        'periode_id',
        'avantage_concede',
        'prelevement_mensuel',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Paie
     *

     * @param  int $employe_id
     * @param  int $periode_id
     * @param  int $avantage_concede
     * @param  int $prelevement_mensuel
     * @param  int $annee_id



     * @return Paie
     */

    public static function addPaie(
        $employe_id,
        $periode_id,
        $avantage_concede,
        $prelevement_mensuel,
        $annee_id

    )
    {
        $paie = new Paie();


        $paie->employe_id = $employe_id;
        $paie->periode_id = $periode_id;
        $paie->avantage_concede = $avantage_concede;
        $paie->prelevement_mensuel = $prelevement_mensuel;
        $paie->annee_id = $annee_id;


        $paie->created_at = Carbon::now();

        $paie->save();

        return $paie;
    }

    /**
     * Affichage d'une activité scolaire
     * @param int $id
     * @return  Paie
     */

    public static function recherchePaieById($id)
    {

        return   $paie = Paie::findOrFail($id);
    }

    /**
     * Update d'une Paie scolaire

    * @param  int $employe_id
     * @param  int $periode_id
     * @param  int $avantage_concede
     * @param  int $prelevement_mensuel
     * @param  int $annee_id




     * @param int $id
     * @return  Paie
     */

    public static function updatePaie(
        $employe_id,
        $periode_id,
        $avantage_concede,
        $prelevement_mensuel,
        $annee_id,

        $id)
    {


        return   $paie = Paie::findOrFail($id)->update([



            'employe_id' => $employe_id,
            'periode_id' => $periode_id,
            'avantage_concede' => $avantage_concede,
            'prelevement_mensuel' => $prelevement_mensuel,
            'annee_id' => $annee_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Paie
     *
     * @param int $id
     * @return  boolean
     */

    public static function deletePaie($id)
    {

        $paie = Paie::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($paie) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Paies
     * @param  int $employe_id
     * @param  int $periode_id
     * @param  int $annee_id

     *
     * @return  array
     */

    public static function getListe(

        $employe_id = null,

        $periode_id = null,
        $annee_id = null

    ) {



        $query =  Paie::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($employe_id != null) {

            $query->where('employe_id', '=', $employe_id);
        }

        if ($periode_id != null) {

            $query->where('periode_id', '=', $periode_id);
        }


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


    * @param  int $employe_id
     * @param  int $periode_id
     * @param  int $annee_id

     * @return  int $total
     */

    public static function getTotal(
        $employe_id = null,

        $periode_id = null,
        $annee_id = null




    ) {

        $query =   DB::table('paies')


            ->where('paies.etat', '!=', TypeStatus::SUPPRIME);


            if ($employe_id != null) {

                $query->where('employe_id', '=', $employe_id);
            }

            if ($periode_id != null) {

                $query->where('periode_id', '=', $periode_id);
            }


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


        return $this->belongsTo(Annee::class, 'prelevement_mensuel');
    }


    /**
     * Obtenir une periode
     *
     */
    public function periode()
    {


        return $this->belongsTo(Periode::class, 'periode_id');
    }


      /**
     * Obtenir un employe 
     *
     */
    public function employe()
    {


        return $this->belongsTo(Employe::class, 'employe_id');
    }


}
