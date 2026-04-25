<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Retenue extends Model
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
        'irpp',

        'annee_id',

        'etat',

    ];



    /**
     * Ajouter une Retenue
     *

     * @param  string $employe_id
     * @param  int $irpp
     * @param  int $annee_id




     * @return Retenue
     */

    public static function addRetenue(
        $employe_id,
        $irpp,
        $annee_id



    )
    {
        $retenue = new Retenue();


        $retenue->employe_id = $employe_id;
        $retenue->irpp = $irpp;

        $retenue->annee_id = $annee_id;



        $retenue->created_at = Carbon::now();

        $retenue->save();

        return $retenue;
    }

    /**
     * Affichage d'une activité scolaire
     * @param int $id
     * @return  Retenue
     */

    public static function rechercheRetenueById($id)
    {

        return   $retenue = Retenue::findOrFail($id);
    }

    /**
     * Update d'une Retenue scolaire

     * @param  string $employe_id
     * @param  int $irpp
     * @param  int $annee_id






     * @param int $id
     * @return  Retenue
     */

    public static function updateRetenue(
        $employe_id,
        $irpp,
        $annee_id,



        $id)
    {


        return   $retenue = Retenue::findOrFail($id)->update([



            'employe_id' => $employe_id,
            'irpp' => $irpp,
            'annee_id' => $annee_id,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Retenue
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteRetenue($id)
    {

        $retenue = Retenue::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($retenue) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Retenues
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



        $query =  Retenue::where('etat', '!=', TypeStatus::SUPPRIME)
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

        $query =   DB::table('retenues')


            ->where('retenues.etat', '!=', TypeStatus::SUPPRIME);






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
