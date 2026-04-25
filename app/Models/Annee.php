<?php

namespace App\Models;

use App\Types\StatutAnnee;
use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Annee extends Model
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
        'date_rentree',
        'date_fin',
        'date_ouverture_inscription',
        'date_fermeture_reinscription',
        'statut_annee',


        'etat',

    ];



    /**
     * Ajouter une Annee
     *

     * @param  string $libelle
     * @param  date $date_rentree
     * @param  date $date_fin
     * @param  date $date_ouverture_inscription
     * @param  date $date_fermeture_reinscription
     * @param  int $statut_annee



     * @return Annee
     */

    public static function addAnnee(
        $libelle,
        $date_rentree,
        $date_fin,
        $date_ouverture_inscription,
        $date_fermeture_reinscription,
        $statut_annee

    )
    {
        $annee = new Annee();


        $annee->libelle = $libelle;
        $annee->date_rentree = $date_rentree;
        $annee->date_fin = $date_fin;
        $annee->date_ouverture_inscription = $date_ouverture_inscription;
        $annee->date_fermeture_reinscription = $date_fermeture_reinscription;
          $annee->statut_annee = $statut_annee;

        $annee->created_at = Carbon::now();

        $annee->save();

        return $annee;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Annee
     */

    public static function rechercheAnneeById($id)
    {

        return   $annee = Annee::findOrFail($id);
    }

    /**
     * Update d'une Annee scolaire

     * @param  string $libelle
     * @param  date $date_rentree
     * @param  date $date_fin
     * @param  date $date_ouverture_inscription
     * @param  date $date_fermeture_reinscription
     * @param  int $statut_annee





     * @param int $id
     * @return  Annee
     */

    public static function updateAnnee(
        $libelle,
        $date_rentree,
        $date_fin,
        $date_ouverture_inscription,
        $date_fermeture_reinscription,
        $statut_annee,

        $id)
    {


        return   $annee = Annee::findOrFail($id)->update([



            'libelle' => $libelle,
            'date_rentree' => $date_rentree,
            'date_fin' => $date_fin,
            'date_ouverture_inscription' => $date_ouverture_inscription,
            'date_fermeture_reinscription' => $date_fermeture_reinscription,
             'statut_annee' => $statut_annee,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Annee
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteAnnee($id)
    {

        $annee = Annee::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($annee) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Annees

     * @param  int $statut_annee

     *
     * @return  array
     */

    public static function getListe(

        $statut_annee = null



    ) {



        $query =  Annee::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($statut_annee != null) {

            $query->where('statut_annee', '=', $statut_annee);
        }



        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


    * @param  int $statut_annee


     * @return  int $total
     */

    public static function getTotal(
        $statut_annee = null



    ) {

        $query =   DB::table('annees')


            ->where('annees.etat', '!=', TypeStatus::SUPPRIME);


        if ($statut_annee != null) {

            $query->where('annees.statut_annee', '=', $statut_annee);
        }



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }


 /**
     * Verifier si une année est ouverte
     *

     * @return  int $id
     */

    public static function getAnneeOuverte()
    {

        $annee = Annee::where('etat', '!=', TypeStatus::SUPPRIME)

            ->where('statut_annee', '=', StatutAnnee::OUVERT)
            ->first();

        if ($annee) {

            return $annee->id;
        }

        return 0;
    }



    /**
     * Génerer l' année en cours
     *

     * @return  Annee $annee
     */

    public static function genererAnneePremiere()
    {

        $libelle ='2024-2025';
        $frais_inscription = 50000;
        $frais_reinscription = 5000;


        $annee = Annee::addAnnee(

            $libelle,
            null,
            null,
            null,
            null,

            StatutAnnee::OUVERT



        );



        return $annee;
    }





}
