<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Pret extends Model
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


        'livre_id',
        'inscription_id',
        'annee_id',
        'date_pret',
        'date_retour_pret',
        'date_retour_reel',
        'statut_pret',


        'etat',

    ];



    /**
     * Ajouter une Pret
     *

     * @param  int $livre_id
     * @param  int $inscription_id
     * @param  int $annee_id
     * @param  date $date_pret
     * @param  date $date_retour_pret
     * @param  date $date_retour_reel
     * @param  int $statut_pret



     * @return Pret
     */

    public static function addPret(
        $livre_id,
        $inscription_id,
        $annee_id,
        $date_pret,
        $date_retour_pret,
        $date_retour_reel,
        $statut_pret


    )
    {
        $Pret = new Pret();


        $Pret->livre_id = $livre_id;
        $Pret->inscription_id = $inscription_id;
        $Pret->annee_id = $annee_id;
        $Pret->date_pret = $date_pret;
        $Pret->date_retour_pret = $date_retour_pret;
        $Pret->date_retour_reel = $date_retour_reel;
        $Pret->statut_pret = $statut_pret;


        $Pret->created_at = Carbon::now();

        $Pret->save();

        return $Pret;
    }

    /**
     * Affichage d'une activité scolaire
     * @param int $id
     * @return  Pret
     */

    public static function recherchePretById($id)
    {

        return   $Pret = Pret::findOrFail($id);
    }

    /**
     * Update d'une Pret scolaire

    * @param  int $livre_id
     * @param  int $inscription_id
     * @param  int $annee_id
     * @param  date $date_pret
     * @param  date $date_retour_pret
     * @param  date $date_retour_reel
     * @param  int $statut_pret




     * @param int $id
     * @return  Pret
     */

    public static function updatePret(
        $livre_id,
        $inscription_id,
        $annee_id,
        $date_pret,
        $date_retour_pret,
        $date_retour_reel,
        $statut_pret,

        $id)
    {


        return   $Pret = Pret::findOrFail($id)->update([



            'livre_id' => $livre_id,
            'inscription_id' => $inscription_id,
            'annee_id' => $annee_id,
            'date_pret' => $date_pret,
            'date_retour_pret' => $date_retour_pret,
            'date_retour_reel' => $date_retour_reel,
            'statut_pret' => $statut_pret,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Pret
     *
     * @param int $id
     * @return  boolean
     */

    public static function deletePret($id)
    {

        $Pret = Pret::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($Pret) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Prets
     * @param  int $livre_id
     * @param  int $inscription_id
     * @param  int $annee_id

     *
     * @return  array
     */

    public static function getListe(

        $livre_id = null,

        $inscription_id = null,
        $annee_id = null

    ) {



        $query =  Pret::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($livre_id != null) {

            $query->where('livre_id', '=', $livre_id);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


    * @param  int $livre_id
     * @param  int $inscription_id
     * @param  int $annee_id

     * @return  int $total
     */

    public static function getTotal(
        $livre_id = null,

        $inscription_id = null,
        $annee_id = null




    ) {

        $query =   DB::table('prets')


            ->where('prets.etat', '!=', TypeStatus::SUPPRIME);


            if ($livre_id != null) {

                $query->where('livre_id', '=', $livre_id);
            }

            if ($inscription_id != null) {

                $query->where('inscription_id', '=', $inscription_id);
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


        return $this->belongsTo(Annee::class, 'annee_id');
    }


    /**
     * Obtenir une periode
     *
     */
    public function inscription()
    {


        return $this->belongsTo(Inscription::class, 'inscription_id');
    }


      /**
     * Obtenir un employe
     *
     */
    public function livre()
    {


        return $this->belongsTo(Livre::class, 'livre_id');
    }


}
