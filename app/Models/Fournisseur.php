<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Fournisseur extends Model
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


        'raison_social',
        'nom_contact',
        'telephone_contact',
        'adresse',
     
       
       

        'etat',

    ];



    /**
     * Ajouter une Fournisseur
     *

     * @param  string  $raison_social
     * @param  string $nom_contact
     * @param  string $telephone_contact
     * @param  text $adresse

  
    

     * @return Fournisseur
     */

    public static function addFournisseur(
        $raison_social,
        $nom_contact,
        $telephone_contact,
        $adresse
     
      
       

    )
    {
        $fournisseur = new Fournisseur();


        $fournisseur->raison_social = $raison_social;
        $fournisseur->nom_contact = $nom_contact;
        $fournisseur->telephone_contact = $telephone_contact;
        $fournisseur->adresse = $adresse;
     
       
       
        $fournisseur->created_at = Carbon::now();

        $fournisseur->save();

        return $fournisseur;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Fournisseur
     */

    public static function rechercheFournisseurById($id)
    {

        return   $fournisseur= Fournisseur::findOrFail($id);
    }

    /**
     * Update d'une Fournisseur scolaire




  * @param  string  $raison_social
     * @param  string $nom_contact
     * @param  string $telephone_contact
     * @param  text $adresse

    
    

     * @param int $id
     * @return  Fournisseur
     */

    public static function updateFournisseur(
       $raison_social,
        $nom_contact,
        $telephone_contact,
        $adresse, 
       
       
        $id)
    {


        return   $fournisseur= Fournisseur::findOrFail($id)->update([



            'raison_social' => $raison_social,
            'nom_contact' => $nom_contact,
            'telephone_contact' => $telephone_contact,
            'adresse' => $adresse,
           
            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Fournisseur
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteFournisseur($id)
    {

        $fournisseur= Fournisseur::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($fournisseur) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Fournisseurs

  
  
 


     *
     * @return  array
     */

    public static function getListe(

      
      


    ) {

      

        $query =  Fournisseur::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

       
       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


  
    

     * @return  int $total
     */

    public static function getTotal(



    ) {

        $query =   DB::table('fournissseurs')


            ->where('fournissseurs.etat', '!=', TypeStatus::SUPPRIME);


       

       


        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   


  

}
