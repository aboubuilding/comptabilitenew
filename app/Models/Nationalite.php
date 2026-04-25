<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Nationalite extends Model
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
     * Ajouter une Nationalite
     *

     * @param  string $libelle



     * @return Nationalite
     */

    public static function addNationalite(
        $libelle

    )
    {
        $nationalite = new Nationalite();


        $nationalite->libelle = $libelle;

        $nationalite->created_at = Carbon::now();

        $nationalite->save();

        return $nationalite;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Nationalite
     */

    public static function rechercheNationaliteById($id)
    {

        return   $nationalite = Nationalite::findOrFail($id);
    }

    /**
     * Update d'une Nationalite scolaire

     * @param  string $libelle






     * @param int $id
     * @return  Nationalite
     */

    public static function updateNationalite(
        $libelle,


        $id)
    {


        return   $nationalite = Nationalite::findOrFail($id)->update([



            'libelle' => $libelle,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Nationalite
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteNationalite($id)
    {

        $nationalite = Nationalite::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($nationalite) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des années
     * @param  int $statut_Nationalite

     *
     * @return  array
     */

    public static function getListe(



    ) {

        $query =  Nationalite::where('etat', '!=', TypeStatus::SUPPRIME)
        ;




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  années




     * @return  int $total
     */

    public static function getTotal(




    ) {

        $query =   DB::table('nationalites')


            ->where('classes.etat', '!=', TypeStatus::SUPPRIME);





        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }




}
