<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Caisse extends Model
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


        'libelle',
        'solde_initial',
        'solde_final',
        'date_ouverture',
        'date_cloture',
        'statut',
        'utilisateur_id',
        'responsable_id',
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Caisse
     *

     * @param  string $libelle
     * @param  int $solde_initial
     * @param  int $solde_final
     * @param  date $date_ouverture
     * @param  date $date_cloture
     * @param  int $statut
     * @param  int $utilisateur_id
     * @param  int $responsable_id
     * @param  int $annee_id





     * @return Caisse
     */

    public static function addCaisse(
        $libelle,
        $solde_initial,
        $solde_final,
        $date_ouverture,
        $date_cloture,
        $statut,
        $utilisateur_id,
        $responsable_id,
        $annee_id


    ) {
        $caisse = new Caisse();


        $caisse->libelle = $libelle;
        $caisse->solde_initial = $solde_initial;
        $caisse->solde_final = $solde_final;
        $caisse->date_ouverture = $date_ouverture;
        $caisse->date_cloture = $date_cloture;
        $caisse->statut = $statut;
        $caisse->utilisateur_id = $utilisateur_id;
        $caisse->responsable_id = $responsable_id;
        $caisse->annee_id = $annee_id;


        $caisse->created_at = Carbon::now();

        $caisse->save();

        return $caisse;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Caisse
     */

    public static function rechercheCaisseById($id)
    {

        return   $caisse = Caisse::findOrFail($id);
    }

    /**
     * Update d'une Caisse scolaire

     * @param  string $libelle
     * @param  int $solde_initial
     * @param  int $solde_final
     * @param  date $date_ouverture
     * @param  date $date_cloture
     * @param  int $statut
     * @param  int $utilisateur_id
     * @param  int $responsable_id
     * @param  int $annee_id



     * @param int $id
     * @return  Caisse
     */

    public static function updateCaisse(
        $libelle,
        $solde_initial,
        $solde_final,
        $date_ouverture,
        $date_cloture,
        $statut,
        $utilisateur_id,
        $responsable_id,
        $annee_id,

        $id
    ) {


        return   $caisse = Caisse::findOrFail($id)->update([



            'libelle' => $libelle,
            'solde_initial' => $solde_initial,
            'solde_final' => $solde_final,
            'date_ouverture' => $date_ouverture,
            'date_cloture' => $date_cloture,
            'statut' => $statut,
            'utilisateur_id' => $utilisateur_id,
            'responsable_id' => $responsable_id,
            'annee_id' => $annee_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Caisse
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCaisse($id)
    {

        $caisse = Caisse::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($caisse) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Caisses
     * @param  int $statut
     * @param  int $utilisateur_id
     * @param  int $responsable_id
     * @param  int $annee_id


     *
     * @return  array
     */

    public static function getListe(

        $statut = null,
        $utilisateur_id = null,
        $responsable_id = null,
        $annee_id = null,
        $libelle = null





    ) {



        $query =  Caisse::where('etat', '!=', TypeStatus::SUPPRIME)
            ->orderBy('caisses.created_at', 'DESC');

        if ($statut != null) {

            $query->where('statut', '=', $statut);
        }

        if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }

        if ($responsable_id != null) {

            $query->where('responsable_id', '=', $responsable_id);
        }

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }
        if ($libelle != null) {

            $query->where('libelle', 'LIKE', '%' . $libelle . '%');
        }






        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités


     * @param  int $statut
     * @param  int $utilisateur_id
     * @param  int $responsable_id
     * @param  int $annee_id



     * @return  int $total
     */

    public static function getTotal(
        $statut = null,
        $utilisateur_id = null,
        $responsable_id = null,
        $annee_id = null



    ) {

        $query =   DB::table('caisses')


            ->where('caisses.etat', '!=', TypeStatus::SUPPRIME);


        if ($statut != null) {

            $query->where('statut', '=', $statut);
        }


        if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }


        if ($responsable_id != null) {

            $query->where('responsable_id', '=', $responsable_id);
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
    public function responsable()
    {


        return $this->belongsTo(User::class, 'responsable_id');
    }


    /**
     * Obtenir un utilisateur
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
