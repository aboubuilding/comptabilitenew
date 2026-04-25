<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DepenseVoiture extends Model
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
        'date_depense',
        'montant',
        'voiture_id',
        'zone_id',
        'type_depense',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une DepenseVoiture
     *

     * @param  string $libelle
     * @param  date $date_depense
     * @param  int $montant

     * @param  int $voiture_id
     * @param  int $zone_id
     *
     *  @param  int $type_depense
     *  @param  int $annee_id



     * @return DepenseVoiture
     */

    public static function addDepenseVoiture(
        $libelle,
        $date_depense,
        $montant,
        $voiture_id,
        $zone_id,
        $type_depense,
        $annee_id,





    )
    {
        $depenseVoiture = new DepenseVoiture();


        $depenseVoiture->libelle = $libelle;
        $depenseVoiture->date_depense = $date_depense;
        $depenseVoiture->montant = $montant;
        $depenseVoiture->voiture_id = $voiture_id;
        $depenseVoiture->zone_id = $zone_id;
        $depenseVoiture->type_depense = $type_depense;

        $depenseVoiture->annee_id = $annee_id;


        $depenseVoiture->created_at = Carbon::now();

        $depenseVoiture->save();

        return $depenseVoiture;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  DepenseVoiture
     */

    public static function rechercheDepenseVoitureById($id)
    {

        return   $depenseVoiture= DepenseVoiture::findOrFail($id);
    }

    /**
     * Update d'une DepenseVoiture scolaire

    * @param  string $libelle
     * @param  date $date_depense
     * @param  int $montant

     * @param  int $voiture_id
     * @param  int $zone_id
     *
     *  @param  int $type_depense
     *  @param  int $annee_id




     * @param int $id
     * @return  DepenseVoiture
     */

    public static function updateDepenseVoiture(
        $libelle,
        $date_depense,
        $montant,
        $voiture_id,
        $zone_id,
        $type_depense,
        $annee_id,


        $id)
    {


        return   $depenseVoiture= DepenseVoiture::findOrFail($id)->update([



            'libelle' => $libelle,
            'date_depense' => $date_depense,
            'montant' => $montant,
            'voiture_id' => $voiture_id,
            'zone_id' => $zone_id,
            'type_depense' => $type_depense,

            'annee_id' => $annee_id,




            'id' => $id,


        ]);
    }




    /**
     * Supprimer une DepenseVoiture
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteDepenseVoiture($id)
    {

        $depenseVoiture= DepenseVoiture::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($depenseVoiture) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des DepenseVoitures

     * @param  int $annee_id
     * @param  int $type_depense
     * @param  int $zone_id
     * @param  int $voiture_id




     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $type_depense = null,
        $zone_id = null,
        $voiture_id = null



    ) {



        $query =  DepenseVoiture::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




         if ($voiture_id != null) {

            $query->where('voiture_id', '=', $voiture_id);
        }


        if ($type_depense != null) {

            $query->where('type_depense', '=', $type_depense);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


       * @param  int $annee_id
     * @param  int $voiture_id
     * @param  int $type_depense



     * @return  int $total
     */

    public static function getTotal(


           $annee_id = null,
        $voiture_id = null,
        $type_depense = null


    ) {

        $query =   DB::table('DepenseVoitures')


            ->where('DepenseVoitures.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }




         if ($voiture_id != null) {

            $query->where('voiture_id', '=', $voiture_id);
        }


        if ($type_depense != null) {

            $query->where('type_depense', '=', $type_depense);
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
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'voiture_id');
    }








}
