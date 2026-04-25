<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Banque extends Model
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
       


        'etat',

    ];



    /**
     * Ajouter une Banque
     *

     * @param  string $nom
    

     * @return Banque
     */

    public static function addBanque(
        $nom
       

    )
    {
        $banque = new Banque();


        $banque->nom = $nom;
      

        $banque->created_at = Carbon::now();

        $banque->save();

        return $banque;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Banque
     */

    public static function rechercheBanqueById($id)
    {

        return   $banque = Banque::findOrFail($id);
    }

    /**
     * Update d'une Banque scolaire

     * @param  string $nom
   
     * @param int $id
     * @return  Banque
     */

    public static function updateBanque(
        $nom,
       

        $id)
    {


        return   $banque = Banque::findOrFail($id)->update([



            'nom' => $nom,
           

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Banque
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteBanque($id)
    {

        $banque = Banque::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($banque) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Banques
   

     *
     * @return  array
     */

    public static function getListe(

       

    ) {

      

        $query =  Banque::where('etat', '!=', TypeStatus::SUPPRIME)
        ;

       


       


        return    $query->get();
    }



    /**
     * Retourne le nombre  des  banques  


    


     * @return  int $total
     */

    public static function getTotal(
       




    ) {

        $query =   DB::table('banques')


            ->where('banques.etat', '!=', TypeStatus::SUPPRIME);


       



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }



 
}