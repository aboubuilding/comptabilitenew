<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Activite extends Model
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
        'description',
        'montant',
        'annee_id',
        'niveau_id',


        'etat',

    ];



    /**
     * Ajouter une Activite
     *

     * @param  string $libelle
     * @param  int $description
     * @param  int $montant
     * @param  int $annee_id
     * @param  int $niveau_id



     * @return Activite
     */

    public static function addActivite(
        $libelle,
        $description,
        $montant,
        $annee_id,
        $niveau_id

    )
    {
        $activite = new Activite();


        $activite->libelle = $libelle;
        $activite->description = $description;
        $activite->montant = $montant;
        $activite->annee_id = $annee_id;
        $activite->niveau_id = $niveau_id;


        $activite->created_at = Carbon::now();

        $activite->save();

        return $activite;
    }

    /**
     * Affichage d'une activité scolaire 
     * @param int $id
     * @return  Activite
     */

    public static function rechercheActiviteById($id)
    {

        return   $activite = Activite::findOrFail($id);
    }

    /**
     * Update d'une Activite scolaire

     * @param  string $libelle
     * @param  int $description
     * @param  int $montant
     * @param  int $annee_id
     * @param  int $niveau_id




     * @param int $id
     * @return  Activite
     */

    public static function updateActivite(
        $libelle,
        $description,
        $montant,
        $annee_id,
        $niveau_id,

        $id)
    {


        return   $activite = Activite::findOrFail($id)->update([



            'libelle' => $libelle,
            'description' => $description,
            'montant' => $montant,
            'annee_id' => $annee_id,
            'niveau_id' => $niveau_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Activite
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteActivite($id)
    {

        $activite = Activite::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($activite) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Activites
     * @param  int $annee_id
     * @param  int $niveau_id

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
      
        $niveau_id = null

    ) {

      

        $query =  Activite::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


    * @param  int $annee_id
     * @param  int $niveau_id


     * @return  int $total
     */

    public static function getTotal(
        $annee_id = null,
        $description = null




    ) {

        $query =   DB::table('activites')


            ->where('activites.etat', '!=', TypeStatus::SUPPRIME);


        if ($niveau_id != null) {

            $query->where('activites.niveau_id', '=', $niveau_id);
        }


        if ($annee_id != null) {

            $query->where('activites.annee_id', '=', $annee_id);
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


}
