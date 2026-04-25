<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Remuneration extends Model
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
        'salaire_base',

        'prime_anciennete',
        'prime_anciennete',
        'sursalaire',
        'bonus',
        'logement',
        'nourriture',
        'deplacement',
        'autre_avantage',
        'annee_id',

        'etat',

    ];



    /**
     * Ajouter une Remuneration
     *

     * @param  string $employe_id
     * @param  int $salaire_base
     * @param  int $prime_anciennete
     * @param  int $sursalaire
     * @param  int $bonus
     * @param  int $logement
     * @param  int $nourriture
     * @param  int $deplacement
     * @param  int $autre_avantage

     * @param  int $annee_id




     * @return Remuneration
     */

    public static function addRemuneration(
        $employe_id,
        $salaire_base,
        $prime_anciennete,
        $sursalaire,
        $bonus,
        $logement,
        $nourriture,
        $deplacement,
        $autre_avantage,

        $annee_id


    )
    {
        $remuneration = new Remuneration();


        $remuneration->employe_id = $employe_id;
        $remuneration->salaire_base = $salaire_base;

        $remuneration->prime_anciennete = $prime_anciennete;
        $remuneration->bonus = $bonus;
        $remuneration->logement = $logement;
        $remuneration->nourriture = $nourriture;
        $remuneration->deplacement = $deplacement;
        $remuneration->autre_avantage = $autre_avantage;
        $remuneration->annee_id = $annee_id;



        $remuneration->created_at = Carbon::now();

        $remuneration->save();

        return $remuneration;
    }

    /**
     * Affichage d'une activité scolaire
     * @param int $id
     * @return  Remuneration
     */

    public static function rechercheRemunerationById($id)
    {

        return   $remuneration = Remuneration::findOrFail($id);
    }

    /**
     * Update d'une Remuneration scolaire

     * @param  string $employe_id
     * @param  int $salaire_base
     * @param  int $prime_anciennete
     * @param  int $sursalaire
     * @param  int $bonus
     * @param  int $logement
     * @param  int $nourriture
     * @param  int $deplacement
     * @param  int $autre_avantage

     * @param  int $annee_id





     * @param int $id
     * @return  Remuneration
     */

    public static function updateRemuneration(
        $employe_id,
        $salaire_base,
        $prime_anciennete,
        $sursalaire,
        $bonus,
        $logement,
        $nourriture,
        $deplacement,
        $autre_avantage,

        $annee_id,


        $id)
    {


        return   $remuneration = Remuneration::findOrFail($id)->update([



            'employe_id' => $employe_id,
            'salaire_base' => $salaire_base,
            'prime_anciennete' => $prime_anciennete,
            'sursalaire' => $sursalaire,
            'bonus' => $bonus,
            'logement' => $logement,
            'nourriture' => $nourriture,
            'deplacement' => $deplacement,

            'autre_avantage' => $autre_avantage,
            'annee_id' => $annee_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Remuneration
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteRemuneration($id)
    {

        $remuneration = Remuneration::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($remuneration) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Remunerations
     *
     *
     * @param  int $annee_id
     * @param  int $employe_id

     *
     * @return  array
     */

    public static function getListe(

        $employe_id = null,
        $annee_id = null



    ) {



        $query =  Remuneration::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($employe_id != null) {

            $query->where('employe_id', '=', $employe_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


    * @param  int $annee_id
     * @param  int $employe_id



     * @return  int $total
     */

    public static function getTotal(

        $employe_id = null,
        $annee_id = null

    ) {

        $query =   DB::table('remunerations')


            ->where('remunerations.etat', '!=', TypeStatus::SUPPRIME);






        if ($employe_id != null) {

            $query->where('employe_id', '=', $employe_id);
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
     * Obtenir une année
     *
     */
    public function employe()
    {


        return $this->belongsTo(Employe::class, 'employe_id');
    }





}
