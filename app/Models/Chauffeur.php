<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Chauffeur extends Model
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


        'nom',
        'prenom',
        'telephone',

        'annee_id',



        'etat',

    ];



    /**
     * Ajouter une Chauffeur
     *

     * @param  string $nom
     * @param  string $prenom
     * @param  string $telephone

     * @param  date $annee_id





     * @return Chauffeur
     */

    public static function addChauffeur(
        $nom,
        $prenom,
        $telephone,

        $annee_id


    )
    {
        $chauffeur = new Chauffeur();


        $chauffeur->nom = $nom;
        $chauffeur->prenom = $prenom;
        $chauffeur->telephone = $telephone;

        $chauffeur->annee_id = $annee_id;



        $chauffeur->created_at = Carbon::now();

        $chauffeur->save();

        return $chauffeur;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Chauffeur
     */

    public static function rechercheChauffeurById($id)
    {

        return   $chauffeur = Chauffeur::findOrFail($id);
    }

    /**
     * Update d'une Chauffeur scolaire

     * @param  string $nom
     * @param  string $prenom
     * @param  string $telephone

     * @param  date $annee_id




     * @param int $id
     * @return  Chauffeur
     */

    public static function updateChauffeur(
      $nom,
        $prenom,
        $telephone,

        $annee_id,

        $id)
    {


        return   $chauffeur = Chauffeur::findOrFail($id)->update([



            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,

            'annee_id' => $annee_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Chauffeur
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteChauffeur($id)
    {

        $chauffeur = Chauffeur::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($chauffeur) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Chauffeurs


     * @param  int $annee_id



     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null


    ) {



        $query =  Chauffeur::where('etat', '!=', TypeStatus::SUPPRIME)
        ;







 if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }









        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités

    * @param  int $annee_id


     * @return  int $total
     */

    public static function getTotal(
        $annee_id = null




    ) {

        $query =   DB::table('chauffeurs')


            ->where('chauffeurs.etat', '!=', TypeStatus::SUPPRIME);


      

 if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }







}
