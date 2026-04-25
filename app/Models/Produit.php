<?php

namespace App\Models;

use App\Types\TypeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Produit extends Model
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
        'prix_unitaire',
        'photo',
        'unite_stock',
        'unite_achat',
        'equivalence',
        'type_produit',



        'etat',

    ];



    /**
     * Ajouter un Produit
     *

     * @param  string $libelle
     * @param  int $prix_unitaire
     * @param  string $photo
     * @param  string $unite_stock
     * @param  string $unite_achat
     * @param  float $equivalence
     * @param  int $type_produit


     * @return Produit
     */

    public static function addProduit(
        $libelle,
        $prix_unitaire,
        $photo,
        $unite_stock,
        $unite_achat,
        $equivalence,
        $type_produit


    )
    {
        $produit = new Produit();


        $produit->libelle = $libelle;
        $produit->prix_unitaire = $prix_unitaire;
        $produit->photo = $photo;
        $produit->unite_stock = $unite_stock;
        $produit->unite_achat = $unite_achat;
        $produit->equivalence = $equivalence;
        $produit->type_produit = $type_produit;


        $produit->created_at = Carbon::now();

        $produit->save();

        return $produit;
    }

    /**
     * Affichage d'une  Produit
     * @param int $id
     * @return  Produit
     */

    public static function rechercheProduitById($id)
    {

        return   $produit = Produit::findOrFail($id);
    }

    /**
     * Update d'une Produit

      * @param  string $libelle
     * @param  int $prix_unitaire
     * @param  string $photo
     * @param  string $unite_stock
     * @param  string $unite_achat
     * @param  float $equivalence
     * @param  int $type_produit



     * @param int $id
     * @return  Produit
     */

    public static function updateProduit(
        $libelle,
        $prix_unitaire,
        $photo,
        $unite_stock,
        $unite_achat,
        $equivalence,
        $type_produit,



        $id)
    {


        return   $produit = Produit::findOrFail($id)->update([



            'libelle' => $libelle,
            'prix_unitaire' => $prix_unitaire,
            'photo' => $photo,
            'unite_stock' => $unite_stock,
            'unite_achat' => $unite_achat,
            'equivalence' => $equivalence,
            'type_produit' => $type_produit,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Produit
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteProduit($id)
    {

        $produit = Produit::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($produit) {
            return 1;
        }
        return 0;
    }






    /**
     * Retourne le nombre de produits    par  annnee ...




         * @return  array
     */

    public static function getListe(





    ){

        $query =  Produit::

           where('produits.etat', '!=', TypeStatus::SUPPRIME)

        ->orderBy('produits.libelle', 'ASC');





          return     $query->get();

    }



    /**
     * Retourne la liste  de produits     par  annnee ...








     * @return  int $total
     */

    public static function getTotal(


    ){

        $query =  Produit::

           where('produits.etat', '!=', TypeStatus::SUPPRIME)
           ;




        $total = $query ->count();

        if($total){

            return   $total;
        }

        return 0;

    }






}
