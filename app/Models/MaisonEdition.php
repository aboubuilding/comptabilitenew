<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaisonEdition extends Model
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
     * Ajouter une MaisonEdition
     *

     * @param  string $libelle






     * @return MaisonEdition
     */

    public static function addMaisonEdition(
        $libelle



    )
    {
        $maisonedition = new MaisonEdition();


        $maisonedition->libelle = $libelle;


        $maisonedition->created_at = Carbon::now();

        $maisonedition->save();

        return $maisonedition;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  MaisonEdition
     */

    public static function rechercheMaisonEditionById($id)
    {

        return   $maisonedition= MaisonEdition::findOrFail($id);
    }

    /**
     * Update d'une MaisonEdition scolaire

   * @param  string $libelle



     * @param int $id
     * @return  MaisonEdition
     */

    public static function updateMaisonEdition(
          $libelle,


        $id)
    {


        return   $maisonedition= MaisonEdition::findOrFail($id)->update([



            'libelle' => $libelle,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une MaisonEdition
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteMaisonEdition($id)
    {

        $maisonedition= MaisonEdition::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($maisonedition) {
            return 1;
        }
        return 0;
    }



    /**


     * Retourne la liste des MaisonEditions





     *
     * @return  array
     */

    public static function getListe(



    ) {



        $query =  MaisonEdition::where('etat', '!=', TypeStatus::SUPPRIME)
        ;










        return    $query->get();
    }



    /**
     * Retourne le nombre  des  activités





     * @return  int $total
     */

    public static function getTotal(




    ) {

        $query =   DB::table('MaisonEditions')


            ->where('MaisonEditions.etat', '!=', TypeStatus::SUPPRIME);





        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }











}
