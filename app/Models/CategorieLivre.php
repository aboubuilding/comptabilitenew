<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CategorieLivre extends Model
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
       


        'etat',

    ];



    /**
     * Ajouter une CategorieLivre
     *

     * @param  int $libelle
   



     * @return CategorieLivre
     */

    public static function addCategorieLivre(
        $libelle
      

    )
    {
        $categorielivre = new CategorieLivre();


        $categorielivre->libelle = $libelle;
       


        $categorielivre->created_at = Carbon::now();

        $categorielivre->save();

        return $categorielivre;
    }

    /**
     * Affichage d'une activité scolaire 
     * @param int $id
     * @return  CategorieLivre
     */

    public static function rechercheCategorieLivreById($id)
    {

        return   $categorielivre = CategorieLivre::findOrFail($id);
    }

    /**
     * Update d'une CategorieLivre scolaire

   * @param  int $libelle
    



     * @param int $id
     * @return  CategorieLivre
     */

    public static function updateCategorieLivre(
       $libelle,
       

        $id)
    {


        return   $categorielivre = CategorieLivre::findOrFail($id)->update([



            'libelle' => $libelle,
            

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une CategorieLivre
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCategorieLivre($id)
    {

        $categorielivre = CategorieLivre::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($categorielivre) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des CategorieLivres
  

     *
     * @return  array
     */

    public static function getListe(

       
    ) {

      

        $query =  CategorieLivre::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

       

       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités 


 


     * @return  int $total
     */

    public static function getTotal(
       



    ) {

        $query =   DB::table('CategorieLivres')


            ->where('CategorieLivres.etat', '!=', TypeStatus::SUPPRIME);


       



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



   


}
