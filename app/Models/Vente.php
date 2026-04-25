<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Vente extends Model
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


        'date_vente',
        'quantite',
        'annee_id',
        'inscription_id',
        'paiement_id',
        'produit_id',
        'detail_id',
        'utilisateur_id',


        'etat',

    ];



    /**
     * Ajouter une Vente
     *

     * @param  date $date_vente
     * @param  int $quantite
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $paiement_id
     * @param  int $produit_id
     * @param  int $detail_id
     * @param  int $utilisateur_id



     * @return Vente
     */

    public static function addVente (

        $date_vente,
        $quantite,
        $annee_id,
        $inscription_id,
        $paiement_id,
        $produit_id,
        $detail_id,
        $utilisateur_id,





    )
    {
        $vente = new Vente();


        $vente->date_vente = $date_vente;
        $vente->quantite = $quantite;
        $vente->annee_id = $annee_id;
        $vente->inscription_id = $inscription_id;
        $vente->paiement_id = $paiement_id;
        $vente->produit_id = $produit_id;
        $vente->detail_id = $detail_id;
        $vente->utilisateur_id = $utilisateur_id;



        $vente->created_at = Carbon::now();

        $vente->save();

        return $vente;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Vente
     */

    public static function rechercheVenteById($id)
    {

        return   $vente= Vente::findOrFail($id);
    }

    /**
     * Update d'une Vente scolaire
     *
     *
     *
 * @param date $date_vente
     * * @param int $quantite
     * * @param int $annee_id
     * * @param int $inscription_id
     * * @param int $paiement_id
     * * @param int $produit_id
     * * @param int $detail_id
     * * @param int $utilisateur_id
 *
     *
     * @param int $id
     * @return  Vente
     */

    public static function updateVente(
        $date_vente,
        $quantite,
        $annee_id,
        $inscription_id,
        $paiement_id,
        $produit_id,
        $detail_id,
        $utilisateur_id,



        $id)
    {


        return   $vente= Vente::findOrFail($id)->update([



            'date_vente' => $date_vente,
            'quantite' => $quantite,
            'annee_id' => $annee_id,
            'inscription_id' => $inscription_id,
            'paiement_id' => $paiement_id,
            'produit_id' => $produit_id,
            'detail_id' => $detail_id,
            'utilisateur_id' => $utilisateur_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Vente
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteVente($id)
    {

        $vente= Vente::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($vente) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Ventes


     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $paiement_id
     * @param  int $produit_id
     * @param  int $detail_id
     * @param  int $utilisateur_id




     *
     * @return  array
     */

    public static function getListe(


        $annee_id = null,
        $inscription_id = null,
        $paiement_id = null,
        $produit_id = null,
        $detail_id = null,
        $utilisateur_id = null


    ) {



        $query =  Vente::where('etat', '!=', TypeStatus::SUPPRIME)
        ;




        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
        }



        if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
        }


         if ($detail_id != null) {

            $query->where('detail_id', '=', $detail_id);
        }


         if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités
 *
     *

     * @param int $annee_id
     * * @param int $inscription_id
     * * @param int $paiement_id
     * * @param int $produit_id
     * * @param int $detail_id
     * * @param int $utilisateur_id
 *
     *
     * @return  int $total
     */

    public static function getTotal(

        $annee_id = null,
        $inscription_id = null,
        $paiement_id = null,
        $produit_id = null,
        $detail_id = null,
        $utilisateur_id = null




    ) {

        $query =   DB::table('ventes')


            ->where('ventes.etat', '!=', TypeStatus::SUPPRIME);




        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($inscription_id != null) {

            $query->where('inscription_id', '=', $inscription_id);
        }


        if ($paiement_id != null) {

            $query->where('paiement_id', '=', $paiement_id);
        }



        if ($produit_id != null) {

            $query->where('produit_id', '=', $produit_id);
        }




         if ($detail_id != null) {

            $query->where('detail_id', '=', $detail_id);
        }


         if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
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
    public function inscription()
    {


        return $this->belongsTo(Inscription::class, 'inscription_id');
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
     * Obtenir un user  
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'utilisateur_id');
    }





    /**
     * Obtenir un produit
     *
     */
    public function produit()
    {


        return $this->belongsTo(Produit::class, 'produit_id');
    }





    /**
     * Retourne la quantite
     *
     *

     * @param int $annee_id
     * * @param int $inscription_id
     * * @param int $paiement_id
     * * @param int $produit_id
     *
     *
     * @return  int $total
     */

    public static function getQuantiteProduit(

        $annee_id = null,
        $inscription_id = null,
        $paiement_id = null,
        $produit_id = null




    ) {




        $total = 0;
        $ventes  = Vente::getListe(
            $annee_id, $inscription_id, $paiement_id, $produit_id
        );


        foreach ($ventes as $vente ){

            $total += $vente->quantite;

        }

        if ($total) {

            return   $total;
        }

        return 0;
    }






    /**
     * Retourne la quantite
     *
     *

     * @param int $annee_id
     * * @param int $inscription_id
     * * @param int $paiement_id
     * * @param int $produit_id
     *
     *
     * @return  int $total
     */

    public static function getMontantProduit(

        $annee_id = null,
        $inscription_id = null,
        $paiement_id = null,
        $produit_id = null




    ) {




        $total = 0;
        $ventes  = Vente::getListe(
            $annee_id, $inscription_id, $paiement_id, $produit_id
        );


        foreach ($ventes as $vente ){

            $total += $vente->quantite*$vente->produit->prix;

        }

        if ($total) {

            return   $total;
        }

        return 0;
    }





}
