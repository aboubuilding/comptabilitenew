<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Asurance extends Model
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


        'nom_assureur',
        'police_assurance',
        'montant_annuel',
     
        'annee_id',


        'etat',

    ];



    /**
     * Ajouter une Asurance
     *

     * @param  string $nom_assureur
     * @param  string $police_assurance
     * @param  int $montant_annuel

     * @param  int $annee_id



     * @return Asurance
     */

    public static function addAsurance(
        $nom_assureur,
        $police_assurance,
        $montant_annuel,
      
        $annee_id

    )
    {
        $asurance = new Asurance();


        $asurance->nom_assureur = $nom_assureur;
        $asurance->police_assurance = $police_assurance;
        $asurance->montant_annuel = $montant_annuel;
     
        $asurance->annee_id = $annee_id;


        $asurance->created_at = Carbon::now();

        $asurance->save();

        return $asurance;
    }

    /**
     * Affichage d'une activité scolaire 
     * @param int $id
     * @return  Asurance
     */

    public static function rechercheAsuranceById($id)
    {

        return   $asurance = Asurance::findOrFail($id);
    }

    /**
     * Update d'une Asurance scolaire

 
     * @param  string $nom_assureur
     * @param  string $police_assurance
     * @param  int $montant_annuel

     * @param  int $annee_id



     * @param int $id
     * @return  Asurance
     */

    public static function updateAsurance(
       $nom_assureur,
        $police_assurance,
        $montant_annuel,
      
        $annee_id, 

        $id)
    {


        return   $asurance = Asurance::findOrFail($id)->update([



            'nom_assureur' => $nom_assureur,
            'police_assurance' => $police_assurance,
            'montant_annuel' => $montant_annuel,
          
            'annee_id' => $annee_id,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Asurance
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteAsurance($id)
    {

        $asurance = Asurance::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($asurance) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Asurances
    
     * @param  int $annee_id

     *
     * @return  array
     */

    public static function getListe(

     
        $annee_id = null

    ) {

      

        $query =  Asurance::where('etat', '!=', TypeStatus::SUPPRIME)
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

        $query =   DB::table('asurances')


            ->where('asurances.etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('asurances.annee_id', '=', $annee_id);
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


   


}
