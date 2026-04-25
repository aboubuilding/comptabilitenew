<?php

namespace App\Models;

use App\Types\TypeStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Achat extends Model
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


        'date_achat',
        'nom_acheteur',
        'reference',
        'bon_commande',
        'commentaire',
        'fournisseur_id',
        'annee_id',
        'statut_paiement',
        'statut_livraison',



        'etat',

    ];



    /**
     * Ajouter un achat
     *

     * @param  date $date_achat
     * @param  string $nom_acheteur
     * @param  string $reference
     * @param  int $bon_commande
     * @param  int $commentaire
     * @param  int $fournisseur_id
     * @param  int $annee_id
     * @param  int $statut_paiement
     * @param  int $statut_livraison



     * @return Achat
     */

    public static function addAchat(
        $date_achat,
        $nom_acheteur,
        $reference,
        $bon_commande,
        $commentaire,
        $fournisseur_id,
        $annee_id,
        $statut_paiement,
        $statut_livraison

    )
    {
        $achat = new Achat();


        $achat->date_achat = $date_achat;
        $achat->nom_acheteur = $nom_acheteur;
        $achat->reference = $reference;
        $achat->bon_commande = $bon_commande;
        $achat->fournisseur_id = $fournisseur_id;
        $achat->annee_id = $annee_id;
        $achat->statut_paiement = $statut_paiement;
        $achat->statut_livraison = $statut_livraison;



        $achat->created_at = Carbon::now();

        $achat->save();

        return $achat;
    }

    /**
     * Affichage d'un achat
     * @param int $id
     * @return  Achat
     */

    public static function rechercheAchatById($id)
    {

        return   $achat = Achat::findOrFail($id);
    }

    /**
     * Update d'une Achat scolaire

    * @param  date $date_achat
     * @param  string $nom_acheteur
     * @param  string $reference
     * @param  int $bon_commande
     * @param  int $commentaire
     * @param  int $fournisseur_id
     * @param  int $annee_id
     * @param  int $statut_paiement
     * @param  int $statut_livraison




     * @param int $id
     * @return  Achat
     */

    public static function updateAchat(
        $date_achat,
        $nom_acheteur,
        $reference,
        $bon_commande,
        $commentaire,
        $fournisseur_id,
        $annee_id,
        $statut_paiement,
        $statut_livraison,

        $id)
    {


        return   $achat = Achat::findOrFail($id)->update([



            'date_achat' => $date_achat,
            'nom_acheteur' => $nom_acheteur,
            'reference' => $reference,
            'bon_commande' => $bon_commande,
            'commentaire' => $commentaire,
            'fournisseur_id' => $fournisseur_id,
            'annee_id' => $annee_id,
            'statut_paiement' => $statut_paiement,
            'statut_livraison' => $statut_livraison,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Achat
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteAchat($id)
    {

        $achat = Achat::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($achat) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Achats


     * @param  int $annee_id
     * @param  int $fournisseur_id
     * @param  int $statut_paiement
     * @param  int $statut_livraison

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,

        $fournisseur_id = null,
        $statut_paiement = null,
        $statut_livraison = null


    ) {



        $query =  Achat::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($fournisseur_id != null) {

            $query->where('fournisseur_id', '=', $fournisseur_id);
        }



         if ($statut_paiement != null) {

            $query->where('statut_paiement', '=', $statut_paiement);
        }


            if ($statut_livraison != null) {

            $query->where('statut_livraison', '=', $statut_livraison);
        }





        return    $query->get();
    }



    /**
     * Retourne le nombre  des  achats


   * @param  int $annee_id
     * @param  int $fournisseur_id
     * @param  int $statut_paiement
     * @param  int $statut_livraison


     * @return  int $total
     */

    public static function getTotal(
         $annee_id = null,

        $fournisseur_id = null,
        $statut_paiement = null,
        $statut_livraison = null





    ) {

        $query =   DB::table('achats')


            ->where('achats.etat', '!=', TypeStatus::SUPPRIME);


       if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($fournisseur_id != null) {

            $query->where('fournisseur_id', '=', $fournisseur_id);
        }



         if ($statut_paiement != null) {

            $query->where('statut_paiement', '=', $statut_paiement);
        }


            if ($statut_livraison != null) {

            $query->where('statut_livraison', '=', $statut_livraison);
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
     * Obtenir un fournisseur
     *
     */
    public function fournisseur()
    {


        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }



     /**
     * Generer le  code de paiement

     * @return  string
     */

     public static function genererNumero()
     {

         $numero = "MAR-ACHT-000";

         $last =  Achat::orderBy('id', 'DESC')
             ->latest()->first();;

         if ($last) {
             $numero = $numero . $last->id;
         }


         return $numero;
     }


}
