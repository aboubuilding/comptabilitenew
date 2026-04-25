<?php

namespace App\Models;

use App\Types\StatutPaiement;
use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Cheque extends Model
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


        'numero',
        'emetteur',
        'annee_id',
        'paiement_id',
        'date_emission',
        'statut',
        'date_encaissement',
        'banque_id',
        'montant',

        'etat',

    ];



    /**
     * Ajouter une Cheque
     *

     * @param  string $numero
     * @param  string $emetteur
     * @param  int $annee_id
     * @param  date $paiement_id
     * @param  date $date_emission
     * @param  int $statut
     * @param  date $date_encaissement
     * @param  int $banque_id
     





     * @return Cheque
     */

    public static function addCheque(
        $numero,
        $emetteur,
        $annee_id,
        $paiement_id,
        $date_emission,
        $statut,
        $date_encaissement,
        $banque_id,
        $montant


    ) {
        $cheque = new Cheque();


        $cheque->numero = $numero;
        $cheque->emetteur = $emetteur;
        $cheque->annee_id = $annee_id;
        $cheque->paiement_id = $paiement_id;
        $cheque->date_emission = $date_emission;
        $cheque->statut = $statut;
        $cheque->date_encaissement = $date_encaissement;
        $cheque->banque_id = $banque_id;
        $cheque->montant = $montant;



        $cheque->created_at = Carbon::now();

        $cheque->save();

        return $cheque;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Cheque
     */

    public static function rechercheChequeById($id)
    {

        return   $cheque = Cheque::findOrFail($id);
    }

    public static function rechercheChequeByPaiement_id($paiement_id)
    {

        return   $cheque = Cheque::where('paiement_id', $paiement_id)->first();
    }
    

    /**
     * Update d'une Cheque scolaire

     * @param  string $numero
     * @param  string $emetteur
     * @param  int $annee_id
     * @param  date $paiement_id
     * @param  date $date_emission
     * @param  int $statut
     * @param  date $date_encaissement
     * @param  int $banque_id
     * @param double $montant
     


     * @param int $id
     * @return  boolean
     */

    public static function updateCheque(
        $numero,
        $emetteur,
        $annee_id,
        $paiement_id,
        $date_emission,
        $statut,
        $date_encaissement,
        $banque_id,
        $montant,
        $id
    ) {


        return   $cheque = Cheque::findOrFail($id)->update([



            'numero' => $numero,
            'emetteur' => $emetteur,
            'annee_id' => $annee_id,
            'paiement_id' => $paiement_id,
            'date_emission' => $date_emission,
            'statut' => $statut,
            'date_encaissement' => $date_encaissement,
            'banque_id' => $banque_id,
            'montant' => $montant,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Cheque
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCheque($id)
    {

        $cheque = Cheque::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($cheque) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Cheques

     * @param  int $banque_id
     * @param  int $statut
     * @param  int $paiement_id
     * @param  int $annee_id


     *
     * @return  array
     */

    public static function getListe(

        $banque_id = null,
        $statut = null,
        $paiement_id = null,
        $annee_id = null





    ) {



        $query =  Cheque::where('etat', '!=', TypeStatus::SUPPRIME);

        if ($banque_id != null) {

            $query->where('banque_id', '=', $banque_id);
        }

        if ($statut != null) {

            $query->where('statut', '=', $statut);
        }

        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        $query->orderBy('statut', 'asc');






        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


     * @param  int $banque_id
     * @param  int $statut
     * @param  int $paiement_id
     * @param  int $annee_id


    

     * @return  int $total
     */

    public static function getTotal(
        $banque_id = null,
        $statut = null,
        $paiement_id = null,
        $annee_id = null



    ) {

        $query =   DB::table('Cheques')


            ->where('Cheques.etat', '!=', TypeStatus::SUPPRIME);


        if ($banque_id != null) {

            $query->where('banque_id', '=', $banque_id);
        }


        if ($statut != null) {

            $query->where('statut', '=', $statut);
        }


        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
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
    public function paiement()
    {


        return $this->belongsTo(Paiement::class, 'paiement_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function banque()
    {


        return $this->belongsTo(Banque::class, 'banque_id');
    }
}
