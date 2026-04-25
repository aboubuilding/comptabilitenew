<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Auteur extends Model
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
       


        'etat',

    ];



    /**
     * Ajouter une Auteur
     *

     * @param  int $nom_prenom
   



     * @return Auteur
     */

    public static function addAuteur(
        $nom_prenom
      

    )
    {
        $auteur = new Auteur();


        $auteur->nom_prenom = $nom_prenom;
       


        $auteur->created_at = Carbon::now();

        $auteur->save();

        return $auteur;
    }

    /**
     * Affichage d'une activité scolaire 
     * @param int $id
     * @return  Auteur
     */

    public static function rechercheAuteurById($id)
    {

        return   $auteur = Auteur::findOrFail($id);
    }

    /**
     * Update d'une Auteur scolaire

   * @param  int $nom_prenom
    



     * @param int $id
     * @return  Auteur
     */

    public static function updateAuteur(
       $nom_prenom,
       

        $id)
    {


        return   $auteur = Auteur::findOrFail($id)->update([



            'nom_prenom' => $nom_prenom,
            

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Auteur
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteAuteur($id)
    {

        $auteur = Auteur::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($auteur) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Auteurs
  

     *
     * @return  array
     */

    public static function getListe(

       
    ) {

      

        $query =  Auteur::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

       

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


 


     * @return  int $total
     */

    public static function getTotal(
       



    ) {

        $query =   DB::table('Auteurs')


            ->where('Auteurs.etat', '!=', TypeStatus::SUPPRIME);


       



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   


}
