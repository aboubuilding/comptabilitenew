<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Souscription extends Model
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


        'date_souscription',
        'montant_annuel_prevu',
        'taux_remise',
        'type_paiement',
        'frais_ecole_id',
        'niveau_id',
        'annee_id',
        'inscription_id',
        'utilisateur_id',
        'ligne_id',



        'etat',

    ];



    /**
     * Ajouter une Souscription
     *

     * @param  date $date_souscription
     * @param  float $montant_annuel_prevu
     * @param  int $taux_remise
     * @param  int $type_paiement
     * @param  int $frais_ecole_id
     * @param  int $niveau_id
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $utilisateur_id
     * @param  int $ligne_id





     * @return Souscription
     */

    public static function addSouscription (

        $date_souscription,
        $montant_annuel_prevu,
        $taux_remise,
        $type_paiement,
        $frais_ecole_id,
        $niveau_id,
        $annee_id,
        $inscription_id,
        $utilisateur_id,
        $ligne_id



    )
    {
        $souscription = new Souscription();


        $souscription->date_souscription = $date_souscription;
        $souscription->montant_annuel_prevu = $montant_annuel_prevu;
        $souscription->taux_remise = $taux_remise;
        $souscription->type_paiement = $type_paiement;
        $souscription->frais_ecole_id = $frais_ecole_id;
        $souscription->niveau_id = $niveau_id;
        $souscription->annee_id = $annee_id;
        $souscription->inscription_id = $inscription_id;
        $souscription->utilisateur_id = $utilisateur_id;
        $souscription->ligne_id = $ligne_id;


        $souscription->created_at = Carbon::now();

        $souscription->save();

        return $souscription;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Souscription
     */

    public static function rechercheSouscriptionById($id)
    {

        return   $souscription= Souscription::findOrFail($id);
    }

    /**
     * Update d'une Souscription scolaire

 ** @param date $date_souscription
     * * @param float $montant_annuel_prevu
     * * @param int $taux_remise
     * * @param int $type_paiement
     * * @param int $frais_ecole_id
     * * @param int $niveau_id
     * * @param int $annee_id
     * * @param int $inscription_id
    * @param  int $utilisateur_id
     * @param  int $ligne_id

     *
     *
 * @param int $id
     * @return  Souscription
     */

    public static function updateSouscription(
        $date_souscription,
        $montant_annuel_prevu,
        $taux_remise,
        $type_paiement,
        $frais_ecole_id,
        $niveau_id,
        $annee_id,
        $inscription_id,
        $utilisateur_id,
        $ligne_id,


        $id)
    {


        return   $souscription= Souscription::findOrFail($id)->update([



            'date_souscription' => $date_souscription,
            'montant_annuel_prevu' => $montant_annuel_prevu,
            'taux_remise' => $taux_remise,
            'type_paiement' => $type_paiement,
            'frais_ecole_id' => $frais_ecole_id,
            'niveau_id' => $niveau_id,
            'annee_id' => $annee_id,
            'inscription_id' => $inscription_id,
            'utilisateur_id' => $utilisateur_id,
            'ligne_id' => $ligne_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Souscription
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteSouscription($id)
    {

        $souscription= Souscription::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($souscription) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Souscriptions

     * @param  int $niveau_id
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $type_paiement
     * @param  int $utilisateur_id
     * @param  int $ligne_id



     *
     * @return  array
     */

    public static function getListe(

        $niveau_id = null,
        $annee_id = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $type_paiement = null,
        $utilisateur_id = null,
        $ligne_id = null,




    ) {



        $query =  Souscription::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

         if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

         if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }


          if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }


         if ($ligne_id != null) {

            $query->where('ligne_id', '=', $ligne_id);
        }



        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités

     *
     *
 * @param int $niveau_id
     * * @param int $annee_id
     * * @param int $inscription_id
     * * @param int $frais_ecole_id
     * * @param int $type_paiement

       * @param  int $utilisateur_id
     * @param  int $ligne_id

     *
     *
 * @return  int $total
     */

    public static function getTotal(
        $niveau_id = null,
        $annee_id = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $type_paiement = null,
        $utilisateur_id = null,
        $ligne_id = null,



    ) {

        $query =   DB::table('souscriptions')


            ->where('souscriptions.etat', '!=', TypeStatus::SUPPRIME);


        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }


         if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }



          if ($ligne_id != null) {

            $query->where('ligne_id', '=', $ligne_id);
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
     * Obtenir un niveau
     *
     */
    public function niveau()
    {


        return $this->belongsTo(Niveau::class, 'niveau_id');
    }



     /**
     * Obtenir un utilisateur
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'utilisateur_id');
    }



     /**
     * Obtenir une ligne 
     *
     */
    public function ligne()
    {


        return $this->belongsTo(Ligne::class, 'ligne_id');
    }





     /**
     * Obtenir une inscription
     *
     */
    public function inscription()
    {


        return $this->belongsTo(Inscription::class, 'inscription_id');
    }



    /**
     * Obtenir un frais
     *
     */
    public function fraisecole()
    {


        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }


    /**
     * Retourne la liste des Souscriptions

     * @param  int $niveau_id
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $type_paiement



     *
     * @return  array
     */

    public static function getPrix(

        $niveau_id = null,
        $annee_id = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $type_paiement = null




    ) {



        $query =  Souscription::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }



        $resultat =   $query->first();

        if($resultat){
             return $resultat;

        }



        return    null;
    }


}
