<?php

namespace App\Models;

use App\Types\StatutMouvement;
use App\Types\TypeMouvement;
use App\Types\TypeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Mouvement extends Model
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


        'libelle',
        'beneficiaire',
        'motif',
        'date_mouvement',
        'montant',
        'type_mouvement',
        'caisse_id',
        'utilisateur_id',
        'paiement_id',
        'depense_id',
        'annee_id',
        'file',
        'etat',

    ];







    /**
     * Ajouter un mouvement
     *

     * @param  string $libelle
     * @param  string $beneficiaire

     * @param  string $motif
     * @param  date $date_mouvement
     * @param  int $montant
     * @param  int $type_mouvement
     * @param  int $caisse_id
     * @param  int $utilisateur_id
     * @param  int $paiement_id
     * @param  int $depense_id
     * @param  int $annee_id
     * @param string $file
     * @param int $statut_mouvement



     * @return Mouvement
     */

    public static function addMouvement(
        $libelle,
        $beneficiaire,
        $motif,

        $date_mouvement,
        $montant,
        $type_mouvement,
        $caisse_id,
        $utilisateur_id,
        $paiement_id,
        $depense_id,

        $annee_id,
          $file,
          $statut_mouvement

    ) {
        $mouvement = new Mouvement();


        $mouvement->libelle = $libelle;
        $mouvement->beneficiaire = $beneficiaire;
        $mouvement->motif = $motif;
        $mouvement->date_mouvement = $date_mouvement;
        $mouvement->montant = $montant;
        $mouvement->type_mouvement = $type_mouvement;
        $mouvement->caisse_id = $caisse_id;
        $mouvement->utilisateur_id = $utilisateur_id;
        $mouvement->paiement_id = $paiement_id;
        $mouvement->depense_id = $depense_id;
        $mouvement->annee_id = $annee_id;
        $mouvement->file = $file;
        $mouvement->statut_mouvement = $statut_mouvement;

        $mouvement->created_at = Carbon::now();

        $mouvement->save();

        return $mouvement;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Mouvement
     */

    public static function rechercheMouvementById($id)
    {

        return   $mouvement = Mouvement::findOrFail($id);
    }

    /**
     * Update d'une mouvement scolaire

     * @param  string $libelle
     * @param  string $beneficiaire
     * @param  string $motif
     * @param  date $date_mouvement
     * @param  float $montant
     * @param  int $type_mouvement
     * @param  int $caisse_id
     * @param  int $utilisateur_id
     * @param  int $paiement_id
     * @param  int $depense_id
     * @param  int $annee_id
     * @param string $file
     * @param int $statut_mouvement



     * @param int $id
     * @return  Mouvement
     */

    public static function updateMouvement(
        $libelle,
        $beneficiaire,
        $motif,
        $date_mouvement,
        $montant,
        $type_mouvement,
        $caisse_id,
        $utilisateur_id,
        $paiement_id,
        $depense_id,
        $file,
        $statut_mouvement,
        $annee_id,

        $id
    ) {


        return   $mouvement = Mouvement::findOrFail($id)->update([



            'libelle' => $libelle,
            'beneficiaire' => $beneficiaire,
            'motif' => $motif,
            'date_mouvement' => $date_mouvement,
            'montant' => $montant,
            'type_mouvement' => $type_mouvement,
            'caisse_id' => $caisse_id,
            'utilisateur_id' => $utilisateur_id,
            'paiement_id' => $paiement_id,
            'depense_id' => $depense_id,
            'annee_id' => $annee_id,
            'file' => $file,
            'statut_mouvement' => $statut_mouvement,
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Mouvement
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteMouvement($id)
    {

        $mouvement = Mouvement::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($mouvement) {
            return 1;
        }
        return 0;
    }


    /**
     * Obtenir un caisse
     *
     */
    public function caisse()
    {


        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    /**
     * Obtenir une utilisateur
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'utilisateur_id');
    }


    /**
     * Obtenir un paiement
     *
     */
    public function paiement()
    {


        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    /**
     * Obtenir une annee
     *
     */
    public function annee()
    {


        return $this->belongsTo(Annee::class, 'annee_id');
    }



    /**
     * Obtenir une annee
     *
     */
    public function depense()
    {


        return $this->belongsTo(Depense::class, 'depense_id');
    }






    /**
     * Retourne la liste des mouvements par ...


     * @param  int $annee_id
     * @param  int $caisse_id
     * @param  int $utilisateur_id

     * @param  int $paiement_id

     * @param  int $type_mouvement
     * @param  int $statut_mouvement





     * @return  array
     */


    public static function getListe(
        $annee_id,
        $caisse_id = null,
        $utilisateur_id = null,

        $paiement_id = null,
        $type_mouvement = null,

        $date1 = null,
        $date2 = null

    ) {

        $query =  Mouvement::where('mouvements.etat', '!=', TypeStatus::SUPPRIME)
            ->orderBy('mouvements.created_at', 'DESC')
            ->where('mouvements.annee_id', '=', $annee_id);

        if ($caisse_id != null) {

            $query->where('mouvements.caisse_id', '=', $caisse_id);
        }

        if ($utilisateur_id != null) {

            $query->where('mouvements.utilisateur_id', '=', $utilisateur_id);
        }



        if ($paiement_id != null) {

            $query->where('mouvements.paiement_id', '=', $paiement_id);
        }

        if ($type_mouvement != null) {

            $query->where('mouvements.type_mouvement', '=', $type_mouvement);
        }


        if ($date1 != null && $date2 != null) {

            $query->whereBetween(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), [$date1, $date2]);
        }


        if ($date1 != null && $date2 == null) {

            $query->where(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), '=', $date1);
        }

        if ($date1 == null && $date2 != null) {

            $query->where(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), '=', $date2);
        }


        return     $query->get();
    }






    /**
     * Retourne le total  pour ...


     * @param  int $annee_id
     * @param  int $caisse_id
     * @param  int $utilisateur_id
     * @param  int $paiement_id
     * @param  int $type_mouvement
     * @param  int $statut_mouvement



     * @return  int $total
     */

    public static function getTotal(
        $annee_id,
        $caisse_id = null,
        $utilisateur_id = null,

        $paiement_id = null,
        $type_mouvement = null,

        $date1 = null,
        $date2 = null

    ) {

        $query =    DB::table('mouvements')

            ->where('mouvements.etat', '!=', TypeStatus::SUPPRIME)
            ->where('mouvements.annee_id', '=', $annee_id)
            ;

        if ($caisse_id != null) {

            $query->where('mouvements.caisse_id', '=', $caisse_id);
        }

        if ($utilisateur_id != null) {

            $query->where('mouvements.utilisateur_id', '=', $utilisateur_id);
        }



        if ($paiement_id != null) {

            $query->where('mouvements.paiement_id', '=', $paiement_id);
        }

        if ($type_mouvement != null) {

            $query->where('mouvements.type_mouvement', '=', $type_mouvement);
        }


        if ($date1 != null && $date2 != null) {

            $query->whereBetween(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), [$date1, $date2]);
        }


        if ($date1 != null && $date2 == null) {

            $query->where(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), '=', $date1);
        }

        if ($date1 == null && $date2 != null) {

            $query->where(DB::raw("DATE_FORMAT(mouvements.date_mouvement, '%Y-%m-%d')"), '=', $date2);
        }




        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



    /**
     * Retourne le total  pour ...


     * @param  int $annee_id
     * @param  int $caisse_id
     * @param  int $utilisateur_id

     * @param  int $paiement_id

     * @param  int $type_mouvement
     * @param  int $statut_mouvement



     * @return  int $total
     */

    public static function getMontantTotal(
        $annee_id,
        $caisse_id = null,
        $utilisateur_id = null,

        $paiement_id = null,
        $type_mouvement = null,

        $date1 = null,
        $date2 = null

    ) {

        $query =    DB::table('mouvements')

            ->where('mouvements.etat', '!=', TypeStatus::SUPPRIME)
            ->where('mouvements.annee_id', '=', $annee_id);

        if ($caisse_id != null) {

            $query->where('mouvements.caisse_id', '=', $caisse_id);
        }

        if ($utilisateur_id != null) {

            $query->where('mouvements.utilisateur_id', '=', $utilisateur_id);
        }



        if ($paiement_id != null) {

            $query->where('mouvements.paiement_id', '=', $paiement_id);
        }

        if ($type_mouvement != null) {

            $query->where('mouvements.type_mouvement', '=', $type_mouvement);
        }


        if ($date1 != null && $date2 != null) {

            $query->whereBetween('mouvements.date_mouvement', [$date1, $date2]);
        }


        if ($date1 != null && $date2 == null) {

            $query->where('mouvements.date_mouvement', '=', $date1);
        }

        if ($date1 == null && $date2 != null) {

            $query->where('mouvements.date_mouvement', '=', $date2);
        }





        $total = $query->SUM('mouvements.montant');

        if ($total) {

            return   $total;
        }

        return 0;
    }
}
