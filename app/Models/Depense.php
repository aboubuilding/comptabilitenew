<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Depense extends Model
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
        'beneficaire',
        'motif_depense',
        'date_depense',
        'montant',
        'annee_id',
        'utilisateur_id',
        'statut_depense',
       
       

        'etat',

    ];



    /**
     * Ajouter une Depense
     *

     * @param  string $libelle
     * @param  string $beneficaire
     * @param  string $motif_depense
     * @param  date $date_depense
     * @param  int $montant
     * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $statut_depense

    

     * @return Depense
     */

    public static function addDepense(
        $libelle,
        $beneficaire,
        $motif_depense,
        $date_depense,
        $montant,
        $annee_id,
        $utilisateur_id,
        $statut_depense
       
        
       

    )
    {
        $depense = new Depense();


        $depense->libelle = $libelle;
        $depense->beneficaire = $beneficaire;
        $depense->motif_depense = $motif_depense;
        $depense->date_depense = $date_depense;
        $depense->montant = $montant;
        $depense->annee_id = $annee_id;

        $depense->utilisateur_id = $utilisateur_id;
        $depense->statut_depense = $statut_depense;
       
        $depense->created_at = Carbon::now();

        $depense->save();

        return $depense;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Depense
     */

    public static function rechercheDepenseById($id)
    {

        return   $depense= Depense::findOrFail($id);
    }

    /**
     * Update d'une Depense scolaire

    * @param  string $libelle
     * @param  string $beneficaire
     * @param  string $motif_depense
     * @param  date $date_depense
     * @param  int $montant
     * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $statut_depense
    
    

     * @param int $id
     * @return  Depense
     */

    public static function updateDepense(
         $libelle,
        $beneficaire,
        $motif_depense,
        $date_depense,
        $montant,
        $annee_id,
        $utilisateur_id,
        $statut_depense,
       
       
        $id)
    {


        return   $depense= Depense::findOrFail($id)->update([



            'libelle' => $libelle,
            'beneficaire' => $beneficaire,
            'motif_depense' => $motif_depense,
            'date_depense' => $date_depense,
            'montant' => $montant,
            'annee_id' => $annee_id,

            'utilisateur_id' => $utilisateur_id,
            'statut_depense' => $statut_depense,


           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Depense
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteDepense($id)
    {

        $depense= Depense::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($depense) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Depenses

     * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $statut_depense
  
 


     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $utilisateur_id = null,
        $statut_depense = null
      


    ) {

      

        $query =  Depense::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        


         if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }


        if ($statut_depense != null) {

            $query->where('statut_depense', '=', $statut_depense);
        }

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


       * @param  int $annee_id
     * @param  int $utilisateur_id
     * @param  int $statut_depense
  
    

     * @return  int $total
     */

    public static function getTotal(


           $annee_id = null,
        $utilisateur_id = null,
        $statut_depense = null


    ) {

        $query =   DB::table('depenses')


            ->where('depenses.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        


         if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }


        if ($statut_depense != null) {

            $query->where('statut_depense', '=', $statut_depense);
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


        return $this->belongsTo(User::class, 'utilisateur_id');
    }


     



  

}
