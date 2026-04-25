<?php

namespace App\Models;

use App\Types\StatutPaiement;
use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Detail extends Model
{
    use HasFactory;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->etat = TypeStatus::ACTIF;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [


        'montant',
        'libelle',
        'paiement_id',
        'type_paiement',
        'inscription_id',
        'frais_ecole_id',
        'statut_paiement',
        'annee_id',
        'souscription_id',
        'caisse_id',
        'comptable_id',
        'caissier_id',
        'date_paiement',
        'date_encaissement',



        'etat',

    ];



    /**
     * Ajouter une Detail
     *

     * @param  int $montant
     * @param  string $libelle
     * @param  int $paiement_id
     * @param  int $type_paiement
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $statut_paiement
     * @param  int $annee_id
     * @param  int $souscription_id
     * @param  int $caisse_id
     * @param  int $comptable_id
     * @param  int $caissier_id
     * @param  date $date_paiement
     * @param  date $date_encaissement
     * @return Detail
     */

    public static function addDetail(
        $montant,
        $libelle,
        $paiement_id,
        $type_paiement,
        $inscription_id,
        $frais_ecole_id,
        $statut_paiement,
        $annee_id,
        $souscription_id,
        $caisse_id,
        $comptable_id,
        $caissier_id,
        $date_paiement,
        $date_encaissement



    ) {
        $detail = new Detail();


        $detail->montant = $montant;
        $detail->libelle = $libelle;
        $detail->paiement_id = $paiement_id;
        $detail->type_paiement = $type_paiement;
        $detail->inscription_id = $inscription_id;
        $detail->frais_ecole_id = $frais_ecole_id;
        $detail->statut_paiement = $statut_paiement;
        $detail->annee_id = $annee_id;
        $detail->souscription_id = $souscription_id;
        $detail->caisse_id = $caisse_id;
        $detail->comptable_id = $comptable_id;
        $detail->caissier_id = $caissier_id;
        $detail->date_paiement = $date_paiement;
        $detail->date_encaissement = $date_encaissement;


        $detail->created_at = Carbon::now();

        $detail->save();

        return $detail;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Detail
     */

    public static function rechercheDetailById($id)
    {

        return   $detail = Detail::findOrFail($id);
    }

    /**
     * Update d'une Detail scolaire
     * @param  int $montant
     * @param  string $libelle
     * @param  int $paiement_id
     * @param  int $type_paiement
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $statut_paiement
     * @param  int $annee_id

     * @param int $souscription_id
     * @param int $caisse_id
     * @param int $comptable_id
     * @param int $caissier_id
     * @param date $date_paiement
     * @param date $date_encaissement
     *
     *
     * @param int $id
     * @return  Detail
     */

    public static function updateDetail(
        $montant,
        $libelle,
        $paiement_id,
        $type_paiement,
        $inscription_id,
        $frais_ecole_id,
        $statut_paiement,
        $annee_id,

        $souscription_id,
        $caisse_id,
        $comptable_id,
        $caissier_id,
        $date_paiement,
        $date_encaissement,


        $id
    ) {


        return   $detail = Detail::findOrFail($id)->update([



            'montant' => $montant,
            'libelle' => $libelle,
            'paiement_id' => $paiement_id,
            'type_paiement' => $type_paiement,
            'inscription_id' => $inscription_id,
            'frais_ecole_id' => $frais_ecole_id,
            'statut_paiement' => $statut_paiement,
            'annee_id' => $annee_id,
            'souscription_id' => $souscription_id,
            'caisse_id' => $caisse_id,
            'comptable_id' => $comptable_id,
            'caissier_id' => $caissier_id,
            'date_paiement' => $date_paiement,
            'date_encaissement' => $date_encaissement,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Detail
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteDetail($id)
    {

        $detail = Detail::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($detail) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Details

     * @param  int $paiement_id
     * @param  int $type_paiement
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $statut_paiement
     * @param  int $annee_id
     * @param  int $souscription_id
     * @param  int $caisse_id
     * @param  int $comptable_id
     * @param  int $caissier_id


     *
     * @return  array
     */

    public static function getListe(

        $paiement_id = null,
        $type_paiement = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $statut_paiement = null,
        $annee_id = null,
        $souscription_id = null,
        $caisse_id = null,
        $comptable_id = null,
        $caissier_id = null,


    ) {



        $query =  Detail::where('etat', '!=', TypeStatus::SUPPRIME);

        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
        }

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }

        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }



        if ($statut_paiement != null) {

            $query->where('statut_paiement', '=', $statut_paiement);
        }



        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }


        if ($souscription_id != null) {

            $query->where('souscription_id', '=', $souscription_id);
        }


        if ($caisse_id != null) {

            $query->where('caisse_id', '=', $caisse_id);
        }


        if ($comptable_id != null) {

            $query->where('comptable_id', '=', $comptable_id);
        }

        if ($caissier_id != null) {

            $query->where('caissier_id', '=', $caissier_id);
        }






        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités
     * @param  int $paiement_id
     * @param  int $type_paiement
     * @param  int $inscription_id
     * @param  int $frais_ecole_id
     * @param  int $statut_paiement
     * @param  int $annee_id
     *
     * @param int $souscription_id
     * @param int $caisse_id
     * @param int $comptable_id
     * @param int $caissier_id

     *
     * @return  int $total
     */

    public static function getTotal(
        $paiement_id = null,
        $type_paiement = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $statut_paiement = null,
        $annee_id = null,

        $souscription_id = null,
        $caisse_id = null,
        $comptable_id = null,
        $caissier_id = null



    ) {

        $query =   DB::table('details')


            ->where('details.etat', '!=', TypeStatus::SUPPRIME);


        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
        }

        if ($type_paiement != null) {

            $query->where('type_paiement', '=', $type_paiement);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }

        if ($frais_ecole_id != null) {

            $query->where('frais_ecole_id', '=', $frais_ecole_id);
        }



        if ($statut_paiement != null) {

            $query->where('statut_paiement', '=', $statut_paiement);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }



        if ($souscription_id != null) {

            $query->where('souscription_id', '=', $souscription_id);
        }


        if ($caisse_id != null) {

            $query->where('caisse_id', '=', $caisse_id);
        }


        if ($comptable_id != null) {

            $query->where('comptable_id', '=', $comptable_id);
        }

        if ($caissier_id != null) {

            $query->where('caissier_id', '=', $caissier_id);
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
    public function inscription()
    {


        return $this->belongsTo(Inscription::class, 'inscription_id');
    }




    /**
     * Obtenir un utilisateur
     *
     */
    public function fraisecole()
    {


        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }



    /**
     * Obtenir un utilisateur
     *
     */
    public function annee()
    {


        return $this->belongsTo(Annee::class, 'annee_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function paiement()
    {


        return $this->belongsTo(Paiement::class, 'paiement_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function souscription()
    {


        return $this->belongsTo(Souscription::class, 'souscription_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function caisse()
    {


        return $this->belongsTo(Caisse::class, 'caisse_id');
    }



    /**
     * Obtenir un utilisateur
     *
     */
    public function comptable()
    {


        return $this->belongsTo(User::class, 'comptable_id');
    }



    /**
     * Obtenir un utilisateur
     *
     */
    public function caissier()
    {


        return $this->belongsTo(User::class, 'caissier_id');
    }


    /**
     * Retourne le motant total  des  paiements par type de frais  ...




     * @return  int $total
     */

    public static function getMontantTotal(
        $annee_id = null,
        $paiement_id = null,
        $type_paiement = null,
        $inscription_id = null,
        $frais_ecole_id = null,
        $statut_paiement = null,
        $date1 = null,
        $date2 = null,

        $souscription_id = null,
        $caisse_id = null,
        $comptable_id = null,
        $caissier_id = null,

        $utilisateur_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,



    ) {

        $query =  DB::table('details')

            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')


            ->where('details.etat', '!=', TypeStatus::SUPPRIME)
            ->where('paiements.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME);

        if ($annee_id != null) {

            $query->where('details.annee_id', '=', $annee_id);
        }
        if ($statut_paiement != null) {
            $query->where('paiements.statut_paiement', '=', $statut_paiement);
        } else {
            $query->where('paiements.statut_paiement', '=', StatutPaiement::ENCAISSE);
        }

        if ($paiement_id != null) {

            $query->where('details.paiement_id', '=', $paiement_id);
        }



        if ($type_paiement != null) {

            $query->where('details.type_paiement', '=', $type_paiement);
        }


        if ($inscription_id != null) {

            $query->where('details.inscription_id', '=', $inscription_id);
        }


        if ($frais_ecole_id != null) {

            $query->where('details.frais_ecole_id', '=', $frais_ecole_id);
        }





        if ($date1 != null && $date2 != null) {

            $query->whereBetween('paiements.date_paiement', [$date1, $date2]);
        }


        if ($date1 != null && $date2 == null) {

            $query->where('paiements.date_paiement', '=', $date1);
        }

        if ($date1 == null && $date2 != null) {

            $query->where('paiements.date_paiement', '=', $date2);
        }


        if ($caisse_id != null) {

            $query->where('details.caisse_id', '=', $caisse_id);
        }

        if ($comptable_id != null) {

            $query->where('details.comptable_id', '=', $comptable_id);
        }


        if ($caissier_id != null) {

            $query->where('details.caissier_id', '=', $caissier_id);
        }


        if ($utilisateur_id != null) {

            $query->where('paiements.utilisateur_id', '=', $utilisateur_id);
        }



        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }



        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }




        $total = $query->SUM('details.montant');

        if ($total) {

            return   $total;
        }

        return 0;
    }









    /**
     * Retourne la liste des souscripteurs a la cantine


     * @param  int $type_paiement

     * @param  int $statut_paiement
     * @param  int $annee_id

     * @param  int $niveau_id
     * @param  int $cycle_id
     * @param  int $classe_id
     * @param  int $frais_ecole_id

     *
     * @return  array
     */

    public static function getListeCantine(

        $type_paiement = null,
        $statut_paiement = null,
        $annee_id = null,
        $niveau_id = null,
        $cycle_id = null,
        $classe_id = null,
        $frais_ecole_id = null


    ) {



        $query =  Detail::select(
            'details.inscription_id as inscription_id',
            'eleves.nom as nom_eleve',
            'eleves.prenom as prenom_eleve',
            'details.frais_ecole_id as offre',
            'cycles.libelle as libelle_cycle',
            'niveaux.libelle as niveau_libelle',
            'inscriptions.frais_cantine as total_a_payer',
            'paiements.reference',
            DB::raw('SUM(details.montant) AS montant_deja_paye')
        )


            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->join('cycles', 'inscriptions.cycle_id', '=', 'cycles.id')
            ->join('niveaux', 'inscriptions.niveau_id', '=', 'niveaux.id')

            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')




            ->where('details.etat', '!=', TypeStatus::SUPPRIME)
            ->where('paiements.etat', '!=', TypeStatus::SUPPRIME);



        if ($type_paiement != null) {

            $query->where('details.type_paiement', '=', $type_paiement);
        }



        if ($frais_ecole_id != null) {

            $query->where('details.frais_ecole_id', '=', $frais_ecole_id);
        }



        if ($statut_paiement != null) {

            $query->where('details.statut_paiement', '=', $statut_paiement);
        }



        if ($annee_id != null) {

            $query->where('details.annee_id', '=', $annee_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        $query->groupBy(
            'details.inscription_id',
            'eleves.nom',
            'eleves.prenom',
            'details.frais_ecole_id',
            'cycles.libelle',
            'niveaux.libelle',
            'inscriptions.frais_cantine',
            'paiements.reference',
        );



        return    $query->get();
    }




    /**
     * Retourne la liste des souscripteurs au bus


     * @param  int $type_paiement

     * @param  int $statut_paiement
     * @param  int $annee_id

     * @param  int $niveau_id
     * @param  int $cycle_id
     * @param  int $classe_id
     * @param  int $frais_ecole_id

     *
     * @return  array
     */

    public static function getListeBus(

        $type_paiement = null,
        $statut_paiement = null,
        $annee_id = null,
        $niveau_id = null,
        $cycle_id = null,
        $classe_id = null,
        $frais_ecole_id = null


    ) {



        $query =  Detail::select(
            'details.inscription_id as inscription_id',
            'details.frais_ecole_id',
            'eleves.nom as nom_eleve',
            'eleves.prenom as prenom_eleve',
            'cycles.libelle as libelle_cycle',
            'niveaux.libelle as niveau_libelle',
            'inscriptions.frais_bus as total_a_payer',
            'paiements.reference',
            DB::raw('SUM(details.montant) AS montant_deja_paye')
        )


            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->join('cycles', 'inscriptions.cycle_id', '=', 'cycles.id')
            ->join('niveaux', 'inscriptions.niveau_id', '=', 'niveaux.id')

            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')


            ->where('details.etat', '!=', TypeStatus::SUPPRIME);



        if ($type_paiement != null) {

            $query->where('details.type_paiement', '=', $type_paiement);
        }



        if ($frais_ecole_id != null) {

            $query->where('details.frais_ecole_id', '=', $frais_ecole_id);
        }



        if ($statut_paiement != null) {

            $query->where('details.statut_paiement', '=', $statut_paiement);
        }



        if ($annee_id != null) {

            $query->where('details.annee_id', '=', $annee_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        $query->groupBy(
            'details.inscription_id',
            'eleves.nom',
            'eleves.prenom',
            'cycles.libelle',
            'niveaux.libelle',
            'inscriptions.frais_cantine'
        );



        return    $query->get();
    }






    /**
     * Retourne le total  des souscripteurs a la cantine


     * @param  int $type_paiement

     * @param  int $statut_paiement
     * @param  int $annee_id

     * @param  int $niveau_id
     * @param  int $cycle_id
     * @param  int $classe_id
     * @param  int $frais_ecole_id

     *
     * @return  int
     */

    public static function getTotalSouscription(

        $type_paiement = null,
        $statut_paiement = null,
        $annee_id = null,
        $frais_ecole_id = null,
        $niveau_id = null,
        $cycle_id = null,
        $classe_id = null


    ) {



        $query =  Detail::select('details.inscription_id')


            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')




            ->where('details.etat', '!=', TypeStatus::SUPPRIME);



        if ($type_paiement != null) {

            $query->where('details.type_paiement', '=', $type_paiement);
        }





        if ($statut_paiement != null) {

            $query->where('details.statut_paiement', '=', $statut_paiement);
        }



        if ($annee_id != null) {

            $query->where('details.annee_id', '=', $annee_id);
        }


        if ($frais_ecole_id != null) {

            $query->where('details.frais_ecole_id', '=', $frais_ecole_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        $query->distinct();

        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }





    /**
     * Retourne la liste des  eleves en classe d examen
     *
     *
     * @param  int $annee_id

     *  @return  array
     */

    public static function getLigneExamen(

        $annee_id = null



    ) {
        $frais_examen = [73, 74];

        $query =  Detail::select(
            'inscriptions.id as inscription_id',
            'eleves.nom as nom_eleve',
            'eleves.prenom as prenom_eleve',
            'niveaux.libelle as niveau_libelle',
            'niveaux.id as niveau_id'
        )

            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->join('niveaux', 'inscriptions.niveau_id', '=', 'niveaux.id')

            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->whereIn('niveaux.id', $niveau_examen);


        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }

        return    $query->get();
    }
}
