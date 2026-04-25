<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Employe extends Model
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


        'nom_prenom',
        'numero_cnss',
        'quartier',
        'ville',
        'telephone',
        'is_cnss',
        'is_amu',
        'nbre_enfant',
        'is_pension_validite',
        'taux_pension_validite',
        'nature_pension_validite',
        'salaire',
        'profession_id',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Employe
     *

     * @param  string $nom_prenom
     * @param  string $numero_cnss
     * @param  string $quartier
     * @param  string $ville
     * @param  string $telephone
     * @param  int $is_cnss
     * @param  int $is_amu
     * @param  int $nbre_enfant
     * @param  int $is_pension_validite
     * @param  int $taux_pension_validite
     * @param  int $nature_pension_validite
     * @param  int $salaire
     * @param  int $profession_id
     * @param  int $annee_id



     * @return Employe
     */

    public static function addEmploye(
        $nom_prenom,
        $numero_cnss,
        $quartier,
        $ville,
        $telephone,
        $is_cnss,
        $is_amu,
        $nbre_enfant,
        $is_pension_validite,
        $taux_pension_validite,
        $nature_pension_validite,
        $salaire,
        $profession_id,
        $annee_id

    )
    {
        $employe = new Employe();


        $employe->nom_prenom = $nom_prenom;
        $employe->numero_cnss = $numero_cnss;
        $employe->quartier = $quartier;
        $employe->ville = $ville;
        $employe->telephone = $telephone;
        $employe->is_cnss = $is_cnss;
        $employe->is_amu = $is_amu;
        $employe->nbre_enfant = $nbre_enfant;
        $employe->is_pension_validite = $is_pension_validite;
        $employe->taux_pension_validite = $taux_pension_validite;
        $employe->nature_pension_validite = $nature_pension_validite;
        $employe->salaire = $salaire;
        $employe->profession_id = $profession_id;
        $employe->annee_id = $annee_id;


        $employe->created_at = Carbon::now();

        $employe->save();

        return $employe;
    }

    /**
     * Affichage d'une activité scolaire
     * @param int $id
     * @return  Employe
     */

    public static function rechercheEmployeById($id)
    {

        return   $employe = Employe::findOrFail($id);
    }

    /**
     * Update d'une Employe scolaire


     * @param  string $nom_prenom
     * @param  string $numero_cnss
     * @param  string $quartier
     * @param  string $ville
     * @param  string $telephone
     * @param  int $is_cnss
     * @param  int $is_amu
     * @param  int $nbre_enfant
     * @param  int $is_pension_validite
     * @param  int $taux_pension_validite
     * @param  int $nature_pension_validite
     * @param  int $salaire
     * @param  int $profession_id
     * @param  int $annee_id





     * @param int $id
     * @return  Employe
     */

    public static function updateEmploye(
        $nom_prenom,
        $numero_cnss,
        $quartier,
        $ville,
        $telephone,
        $is_cnss,
        $is_amu,
        $nbre_enfant,
        $is_pension_validite,
        $taux_pension_validite,
        $nature_pension_validite,
        $salaire,
        $profession_id,
        $annee_id,

        $id)
    {


        return   $employe = Employe::findOrFail($id)->update([



            'nom_prenom' => $nom_prenom,
            'numero_cnss' => $numero_cnss,
            'quartier' => $quartier,
            'ville' => $ville,
            'telephone' => $telephone,
            'is_cnss' => $is_cnss,
            'is_amu' => $is_amu,
            'nbre_enfant' => $nbre_enfant,
            'is_pension_validite' => $is_pension_validite,
            'taux_pension_validite' => $taux_pension_validite,
            'nature_pension_validite' => $nature_pension_validite,
            'salaire' => $salaire,
            'profession_id' => $profession_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Employe
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteEmploye($id)
    {

        $employe = Employe::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($employe) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Employes
     * @param  int $annee_id
     * @param  int $profession_id
     * @param  int $is_cnss
     * @param  int $is_amu
     * @param  int $is_pension_validite

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,

        $profession_id = null,
        $is_cnss = null,
        $is_amu = null,
        $is_pension_validite = null

    ) {



        $query =  Employe::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($profession_id != null) {

            $query->where('profession_id', '=', $profession_id);
        }


        if ($is_cnss != null) {

            $query->where('is_cnss', '=', $is_cnss);
        }


        if ($is_amu != null) {

            $query->where('is_amu', '=', $is_amu);
        }


        if ($is_pension_validite != null) {

            $query->where('is_pension_validite', '=', $is_pension_validite);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  employes


     * @param  int $annee_id
     * @param  int $profession_id
     * @param  int $is_cnss
     * @param  int $is_amu
     * @param  int $is_pension_validite


     * @return  int $total
     */

    public static function getTotal(
        $annee_id = null,

        $profession_id = null,
        $is_cnss = null,
        $is_amu = null,
        $is_pension_validite = null




    ) {

        $query =   DB::table('employes')


            ->where('employes.etat', '!=', TypeStatus::SUPPRIME);


            if ($annee_id != null) {

                $query->where('annee_id', '=', $annee_id);
            }

            if ($profession_id != null) {

                $query->where('profession_id', '=', $profession_id);
            }


            if ($is_cnss != null) {

                $query->where('is_cnss', '=', $is_cnss);
            }


            if ($is_amu != null) {

                $query->where('is_amu', '=', $is_amu);
            }


            if ($is_pension_validite != null) {

                $query->where('is_pension_validite', '=', $is_pension_validite);
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
     * Obtenir une profession 
     *
     */
    public function profession()
    {


        return $this->belongsTo(Profession::class, 'profession_id');
    }


}
