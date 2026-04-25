<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FraisEcole extends Model
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
        'montant',
        'type_paiement',
        'type_forfait',
        'niveau_id',

        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une FraisEcole
     *

     * @param  string $libelle

     * @param  int $montant
     * @param  int $type_paiement
     * @param  int $type_forfait
     * @param  int $niveau_id
     * @param  int $annee_id




     * @return FraisEcole
     */

    public static function addFraisEcole(
        $libelle,

        $montant,
        $type_paiement,
        $type_forfait,
        $niveau_id,
        $annee_id


    )
    {
        $fraisecole = new FraisEcole();


        $fraisecole->libelle = $libelle;

        $fraisecole->montant = $montant;
        $fraisecole->type_paiement = $type_paiement;
        $fraisecole->type_forfait = $type_forfait;
        $fraisecole->niveau_id = $niveau_id;
        $fraisecole->annee_id = $annee_id;

        $fraisecole->created_at = Carbon::now();

        $fraisecole->save();

        return $fraisecole;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  FraisEcole
     */

    public static function rechercheFraisEcoleById($id)
    {

        return   $fraisecole= FraisEcole::findOrFail($id);
    }

    /**
     * Update d'une FraisEcole scolaire
     *
     *
     *
 * @param string $libelle
     *
     * * @param int $montant
     * * @param int $type_paiement
     * * @param int $type_forfait
     * * @param int $niveau_id
     * * @param int $annee_id
 * @param int $id
     * @return  FraisEcole
     */

    public static function updateFraisEcole(
        $libelle,

        $montant,
        $type_paiement,
        $type_forfait,
        $niveau_id,
        $annee_id,


        $id)
    {


        return   $fraisecole= FraisEcole::findOrFail($id)->update([



            'libelle' => $libelle,

            'montant' => $montant,
            'type_paiement' => $type_paiement,
            'type_forfait' => $type_forfait,
            'niveau_id' => $niveau_id,
            'annee_id' => $annee_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une FraisEcole
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteFraisEcole($id)
    {

        $fraisecole= FraisEcole::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($fraisecole) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des FraisEcoles


     * @param  int $type_paiement
     * @param  int $type_forfait
     * @param  int $niveau_id
     * @param  int $annee_id


     *
     * @return  array
     */

    public static function getListe(


        $type_paiement = null,
        $type_forfait = null,
        $niveau_id = null,
        $annee_id = null





    ) {



        $query =  FraisEcole::where('etat', '!=', TypeStatus::SUPPRIME)
        ;



         if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }

         if ($type_forfait != null) {

            $query->where('type_forfait', '=', $type_forfait);
        }

         if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }






        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités



    * @param  int $type_paiement
     * @param  int $type_forfait
     * @param  int $niveau_id
     * @param  int $annee_id


     * @return  int $total
     */

    public static function getTotal(

  		$type_paiement = null,
        $type_forfait = null,
        $niveau_id = null,
        $annee_id = null





    ) {

        $query =   DB::table('frais_ecoles')


            ->where('frais_ecoles.etat', '!=', TypeStatus::SUPPRIME);



        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }

         if ($type_forfait != null) {

            $query->where('type_forfait', '=', $type_forfait);
        }

         if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
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
    public function niveau()
    {


        return $this->belongsTo(Niveau::class, 'niveau_id');
    }




    public static function getPrix(

        $type_paiement = null,
        $type_forfait = null,
        $niveau_id = null,
        $annee_id = null




    ) {



        $query =  FraisEcole::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }

        if ($type_forfait != null) {

            $query->where('type_forfait', '=', $type_forfait);
        }

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }



        $resultat =   $query->first();

        if($resultat){
            return $resultat;

        }



        return    null;
    }






}
