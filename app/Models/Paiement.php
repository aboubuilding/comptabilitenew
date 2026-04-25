<?php

namespace App\Models;

use App\Types\ModePaiement;
use App\Types\StatutPaiement;
use App\Types\TypePaiement;
use App\Types\TypeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sum;
use Ramsey\Uuid\Type\Decimal;

class Paiement extends Model
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


        'reference',
        'payeur',
        'motif_suppression',
        'telephone_payeur',
        'date_paiement',
        'date_encaissement',
        'statut_paiement',
        'mode_paiement',
        'inscription_id',
        'utilisateur_id',
        'cheque_id',
        'annee_id',
        'montant',


        'etat',

    ];







    /**
     * Ajouter un paiement
     *

     * @param  string $reference
     * @param  string $payeur
     * @param  string $motif_suppression
     * @param  string $telephone_payeur
     * @param  date $date_paiement
     * @param  int $statut_paiement
     * @param  int $mode_paiement
     * @param  int $inscription_id
     * @param  int $utilisateur_id
     * @param  int $cheque_id
     * @param  int $annee_id
     * @param  Decimal $montant






     * @return Paiement
     */

    public static function addPaiement(
        $reference,
        $payeur,
        $motif_suppression,
        $telephone_payeur,
        $date_paiement,
        $statut_paiement,
        $mode_paiement,
        $inscription_id,
        $utilisateur_id,
        $cheque_id,
        $annee_id,
        $montant

    ) {
        $paiement = new Paiement();


        $paiement->reference = $reference;
        $paiement->payeur = $payeur;
        $paiement->motif_suppression = $motif_suppression;
        $paiement->telephone_payeur = $telephone_payeur;
        $paiement->date_paiement = $date_paiement;
        $paiement->statut_paiement = $statut_paiement;
        $paiement->mode_paiement = $mode_paiement;
        $paiement->inscription_id = $inscription_id;
        $paiement->utilisateur_id = $utilisateur_id;
        $paiement->cheque_id = $cheque_id;
        $paiement->annee_id = $annee_id;
        $paiement->montant = $montant;

        $paiement->created_at = Carbon::now();

        $paiement->save();

        return $paiement;
    }

    /**
     * Affichage d'une année scolaire
     *
     *
     * @param int $id
     * @return  Paiement
     */

    public static function recherchePaiementById($id)
    {

        return   $paiement = Paiement::findOrFail($id);
    }

    /**
     * Update d'une paiement scolaire

     * @param  string $reference
     * @param  string $payeur
     * @param  string $motif_suppression
     * @param  string $telephone_payeur
     * @param  date $date_paiement
     * @param  int $statut_paiement
     * @param  int $mode_paiement
     * @param  int $inscription_id
     * @param  int $utilisateur_id
     * @param  int $cheque_id
     * @param  int $annee_id
     * @param  float $montant


     * @param int $id
     * @return  Paiement
     */

    public static function updatePaiement(
        $reference,
        $payeur,
        $motif_suppression,
        $telephone_payeur,
        $date_paiement,
        $statut_paiement,
        $mode_paiement,
        $inscription_id,
        $utilisateur_id,
        $cheque_id,
        $annee_id,
        $montant,




        $id
    ) {


        return   $paiement = Paiement::findOrFail($id)->update([



            'reference' => $reference,
            'payeur' => $payeur,
            'motif_suppression' => $motif_suppression,

            'telephone_payeur' => $telephone_payeur,
            'date_paiement' => $date_paiement,

            'statut_paiement' => $statut_paiement,

            'mode_paiement' => $mode_paiement,
            'inscription_id' => $inscription_id,

            'utilisateur_id' => $utilisateur_id,
            'cheque_id' => $cheque_id,

            'annee_id' => $annee_id,
            'montant' => $montant,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Paiement
     *
     * @param int $id
     * @return  boolean
     */

    public static function deletePaiement($id)
    {

        $paiement = Paiement::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($paiement) {
            return 1;
        }
        return 0;
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
    public function cheque()
    {


        return $this->belongsTo(Cheque::class, 'cheque_id');
    }

    /**
     * Obtenir une UTILISATEUR
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'utilisateur_id');
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
     * Generer le  code de paiement

     * @return  string
     */

    public static function genererNumero()
    {

        $numero = "MAR-PAI-000";

        $last =  Paiement::orderBy('id', 'DESC')
            ->latest()->first();;

        if ($last) {
            $numero = $numero . $last->id;
        }


        return $numero;
    }




    /**
     * Retourne la liste des  paiements par ...


     * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $inscription_id

     * @param  int $mode_paiement

     * @param  int $statut_paiement



     * @return  array
     */

    public static function getListe(
        $annee_id,
        $utilisateur_id = null,
        $inscription_id = null,

        $mode_paiement = null,
        $statut_paiement = null,

        $date1 = null,
        $date2 = null,

    ) {

        $query =  Paiement::where('paiements.etat', '!=', TypeStatus::SUPPRIME)
            ->orderBy('paiements.created_at', 'DESC');

        if ($utilisateur_id != null) {

            $query->where('paiements.utilisateur_id', '=', $utilisateur_id);
        }

        if ($annee_id != null) {

            $query->where('paiements.annee_id', '=', $annee_id);
        }

        if ($inscription_id != null) {

            $query->where('paiements.inscription_id', '=', $inscription_id);
        }



        if ($mode_paiement != null) {

            $query->where('paiements.mode_paiement', '=', $mode_paiement);
        }

        if ($statut_paiement != null) {

            $query->where('paiements.statut_paiement', '=', $statut_paiement);
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


        return     $query->get();
    }




    /**
     * Retourne le total  des  paiements par ...


     * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $inscription_id



     * @param  int $mode_paiement

     * @param  int $statut_paiement
     * @param  date $date1
     * @param  date $date2



     * @return  int $total
     */

    public static function getTotal(
        $annee_id,
        $utilisateur_id = null,
        $inscription_id = null,

        $mode_paiement = null,
        $statut_paiement = null,
        $date1  = null,
        $date2 = null

    ) {

        $query =   Paiement::where('paiements.etat', '!=', TypeStatus::SUPPRIME)
            ->orderBy('paiements.created_at', 'DESC')
            ->where('paiements.annee_id', '=', $annee_id);



        if ($utilisateur_id != null) {

            $query->where('paiements.utilisateur_id', '=', $utilisateur_id);
        }

        if ($inscription_id != null) {

            $query->where('paiements.inscription_id', '=', $inscription_id);
        }



        if ($mode_paiement != null) {

            $query->where('paiements.mode_paiement', '=', $mode_paiement);
        }

        if ($statut_paiement != null) {

            $query->where('paiements.statut_paiement', '=', $statut_paiement);
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


        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }








    /**
     * Update d'une Budget scolaire

     * @param  string $libelle
     * @param  string $description
     * @param  int $annee_id



     * @param int $id
     * @return  Budget
     */

    public static function supprimerPaiement(

        $motif_suppression,


        $id
    ) {


        /*     DB::beginTransaction();

        try {*/
        $paiement = Paiement::recherchePaiementById($id);

        Paiement::findOrFail($id)->update([



            'motif_suppression' => $motif_suppression,
            'etat' => TypeStatus::SUPPRIME,

            'id' => $id,


        ]);
        // Si le mode de paiement est un chèque, on supprime le chèque associé
        // On vérifie si le paiement a un chèque associé
        if ($paiement->mode_paiement == ModePaiement::CHEQUE) {

            $cheque = Cheque::rechercheChequeByPaiement_id($id);
            $cheque->etat = TypeStatus::SUPPRIME;
            $cheque->save();
        }

        $details = Detail::getListe($id);


        foreach ($details as $detail) {

            Detail::deleteDetail($detail->id);
        }

        //            DB::commit();

        // Retourner une réponse à l'utilisateur
        return response()->json(['message' => 'Paiement  supprimée  avec succès'], 200);
        /* } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            DB::rollBack();

            // Retourner une réponse d'erreur
            return response()->json(['message' => 'Erreur lors de la sauvegarde des données : ' . $e->getMessage()], 500);
        }*/
    }

    public function details()
    {
        return $this->hasMany(Detail::class, 'paiement_id');
    }

    public function detailsValides()
    {
        return $this->hasMany(Detail::class, 'paiement_id')->where('etat', TypeStatus::ACTIF);
    }
}
