<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Tranche extends Model
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
        'date_butoire',
        'frais_ecole_id',
        'type_frais',
        'taux',




        'etat',

    ];



    /**
     * Ajouter une Tranche
     *

     * @param  date $libelle
     * @param  float $date_butoire
     * @param  int $frais_ecole_id
     * @param  int $type_frais
     * @param  int $taux




     * @return Tranche
     */

    public static function addTranche (

        $libelle,
        $date_butoire,
        $frais_ecole_id,
        $type_frais,
        $taux




    )
    {
        $tranche = new Tranche();


        $tranche->libelle = $libelle;
        $tranche->date_butoire = $date_butoire;
        $tranche->frais_ecole_id = $frais_ecole_id;
        $tranche->type_frais = $type_frais;
        $tranche->taux = $taux;



        $tranche->created_at = Carbon::now();

        $tranche->save();

        return $tranche;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Tranche
     */

    public static function rechercheTrancheById($id)
    {

        return   $tranche= Tranche::findOrFail($id);
    }

    /**
     * Update d'une Tranche scolaire

     ** @param date $libelle
     * * @param float $date_butoire
     * * @param int $frais_ecole_id
     * * @param int $type_frais
     * * @param int $taux

     *
     *
     * @param int $id
     * @return  Tranche
     */

    public static function updateTranche(
        $libelle,
        $date_butoire,
        $frais_ecole_id,
        $type_frais,
        $taux,



        $id)
    {


        return   $tranche= Tranche::findOrFail($id)->update([



            'libelle' => $libelle,
            'date_butoire' => $date_butoire,
            'frais_ecole_id' => $frais_ecole_id,
            'type_frais' => $type_frais,
            'taux' => $taux,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Tranche
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteTranche($id)
    {

        $tranche= Tranche::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($tranche) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Tranches


     * @param  int $frais_ecole_id




     *
     * @return  array
     */

    public static function getListe(


        $frais_ecole_id = null,
        $type_frais = null


    ) {



        $query =  Tranche::where('etat', '!=', TypeStatus::SUPPRIME)
        ;




        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }



          if ($type_frais != null) {

            $query->where('type_frais', '=', $type_frais);
        }



        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités

     *
     *

     * * @param int $frais_ecole_id


     *
     *
     * @return  int $total
     */

    public static function getTotal(

        $frais_ecole_id = null




    ) {

        $query =   DB::table('Tranches')


            ->where('Tranches.etat', '!=', TypeStatus::SUPPRIME);




        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
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
    public function fraisecole()
    {


        return $this->belongsTo(\FraisEcole::class, 'frais_ecole_id');
    }










}
