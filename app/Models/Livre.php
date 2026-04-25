<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Livre extends Model
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


        'auteur_id',
        'maison_edition_id',
        'annee_id',
        'categorie_livre_id',
        'annee_edition_id',
        'titre',
        'numero',
        'numero_isbn',


        'etat',

    ];



    /**
     * Ajouter un Livre
     *

     * @param  int $auteur_id
     * @param  int $maison_edition_id
     * @param  int $annee_id
     * @param  int $categorie_livre_id
     * @param  int $annee_edition_id
     * @param  string $titre
     * @param  string $numero
     * @param  string $numero_isbn






     * @return Livre
     */

    public static function addLivre(
        $auteur_id,
        $maison_edition_id,
        $annee_id,
        $categorie_livre_id,
        $annee_edition_id,
        $titre,
        $numero,
        $numero_isbn


    )
    {
        $livre = new Livre();


        $livre->auteur_id = $auteur_id;
        $livre->maison_edition_id = $maison_edition_id;
        $livre->annee_id = $annee_id;
        $livre->categorie_livre_id = $categorie_livre_id;
        $livre->annee_edition_id = $annee_edition_id;
        $livre->titre = $titre;
        $livre->numero = $numero;
        $livre->numero_isbn = $numero_isbn;

        $livre->created_at = Carbon::now();

        $livre->save();

        return $livre;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Livre
     */

    public static function rechercheLivreById($id)
    {

        return   $livre= Livre::findOrFail($id);
    }

    /**
     * Update d'une Livre scolaire

    * @param  int $auteur_id
     * @param  int $maison_edition_id
     * @param  int $annee_id
     * @param  int $categorie_livre_id
     * @param  int $annee_edition_id
     * @param  string $titre
     * @param  string $numero
     * @param  string $numero_isbn


     * @param int $id
     * @return  Livre
     */

    public static function updateLivre(
        $auteur_id,
        $maison_edition_id,
        $annee_id,
        $categorie_livre_id,
        $annee_edition_id,
        $titre,
        $numero,
        $numero_isbn,


        $id)
    {


        return   $livre= Livre::findOrFail($id)->update([



            'auteur_id' => $auteur_id,
            'maison_edition_id' => $maison_edition_id,
            'annee_id' => $annee_id,
            'categorie_livre_id' => $categorie_livre_id,
            'annee_edition_id' => $annee_edition_id,
            'titre' => $titre,
            'numero' => $numero,
            'numero_isbn' => $numero_isbn,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Livre
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteLivre($id)
    {

        $livre= Livre::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($livre) {
            return 1;
        }
        return 0;
    }



    /**


     * Retourne la liste des Livres

     * @param  int $auteur_id
     * @param  int $maison_edition_id
     * @param  int $annee_id
     * @param  int $categorie_livre_id
     * @param  int $annee_edition_id



     *
     * @return  array
     */

    public static function getListe(

        $auteur_id = null,
        $maison_edition_id = null,
        $annee_id = null,
        $categorie_livre_id = null,
        $annee_edition_id = null





    ) {



        $query =  Livre::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($auteur_id != null) {

            $query->where('auteur_id', '=', $auteur_id);
        }

         if ($maison_edition_id != null) {

            $query->where('maison_edition_id', '=', $maison_edition_id);
        }


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }



        if ($categorie_livre_id != null) {

            $query->where('categorie_livre_id', '=', $categorie_livre_id);
        }


        if ($annee_edition_id != null) {

            $query->where('annee_edition_id', '=', $annee_edition_id);
        }








        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


     * @param  int $auteur_id
     * @param  int $maison_edition_id
     * @param  int $annee_id
     * @param  int $categorie_livre_id
     * @param  int $annee_edition_id




     * @return  int $total
     */

    public static function getTotal(
        $auteur_id = null,
        $maison_edition_id = null,
        $annee_id = null,
        $categorie_livre_id = null,
        $annee_edition_id = null



    ) {

        $query =   DB::table('livres')


            ->where('livres.etat', '!=', TypeStatus::SUPPRIME);


            if ($auteur_id != null) {

                $query->where('auteur_id', '=', $auteur_id);
            }

             if ($maison_edition_id != null) {

                $query->where('maison_edition_id', '=', $maison_edition_id);
            }


            if ($annee_id != null) {

                $query->where('annee_id', '=', $annee_id);
            }



            if ($categorie_livre_id != null) {

                $query->where('categorie_livre_id', '=', $categorie_livre_id);
            }


            if ($annee_edition_id != null) {

                $query->where('annee_edition_id', '=', $annee_edition_id);
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
     * Obtenir un utilisateur
     *
     */
    public function auteur()
    {


        return $this->belongsTo(Auteur::class, 'auteur_id');
    }



     /**
     * Obtenir un utilisateur
     *
     */
    public function maisonedition()
    {


        return $this->belongsTo(MaisonEdition::class, 'maison_edition_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function categorielivre()
    {


        return $this->belongsTo(CategorieLivre::class, 'categorie_livre_id');
    }


    /**
     * Obtenir une année d 'edition
     *
     */
    public function anneedition()
    {


        return $this->belongsTo(AnneEdition::class, 'annee_edition_id');
    }









}
