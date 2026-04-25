<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Voiture extends Model
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



        'marque',
        'plaque',
        'nombre_place',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Voiture
     *


     * @param  string $marque
     * @param  string $plaque
     * @param  int $nombre_place
     * @param  int $annee_id



     * @return Voiture
     */

    public static function addVoiture (


        $marque,
        $plaque,
        $nombre_place,
        $annee_id





    )
    {
        $voiture = new Voiture();



        $voiture->marque = $marque;
        $voiture->plaque = $plaque;
        $voiture->nombre_place = $nombre_place;
        $voiture->annee_id = $annee_id;



        $voiture->created_at = Carbon::now();

        $voiture->save();

        return $voiture;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Voiture
     */

    public static function rechercheVoitureById($id)
    {

        return   $voiture= Voiture::findOrFail($id);
    }

    /**
     * Update d'une Voiture scolaire
     *
     *


     * @param  string $marque
     * @param  string $plaque
     * @param  int $nombre_place
     * @param  int $annee_id
     *
     *
     * @param int $id
     * @return  Voiture
     */

    public static function updateVoiture(

        $marque,
        $plaque,
        $nombre_place,
        $annee_id,



        $id)
    {


        return   $voiture= Voiture::findOrFail($id)->update([




            'marque' => $marque,
            'plaque' => $plaque,
            'nombre_place' => $nombre_place,
            'annee_id' => $annee_id,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Voiture
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteVoiture($id)
    {

        $voiture= Voiture::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($voiture) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Voitures



     * @param  int $annee_id




     *
     * @return  array
     */

    public static function getListe(



        $annee_id = null



    ) {



        $query =  Voiture::where('etat', '!=', TypeStatus::SUPPRIME)
        ;





        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }





        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités
     *
     *

     * * @param int $annee_id

 *
     *
     * @return  int $total
     */

    public static function getTotal(


        $annee_id = null


    ) {

        $query =   DB::table('voitures')


            ->where('voitures.etat', '!=', TypeStatus::SUPPRIME);




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


        return $this->belongsTo(Annee::class, 'plaque');
    }









}
