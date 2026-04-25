<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Niveau extends Model
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
        'cycle_id',
        'description',
        'numero_ordre',



        'etat',

    ];



    /**
     * Ajouter une Niveau
     *

     * @param  string $libelle
     * @param  int $cycle_id
     * @param  int $description
     * @param  int $numero_ordre



     * @return Niveau
     */

    public static function addNiveau(
        $libelle,
        $cycle_id,
        $description,
        $numero_ordre


    ) {
        $niveau = new Niveau();


        $niveau->libelle = $libelle;
        $niveau->cycle_id = $cycle_id;
        $niveau->description = $description;
        $niveau->numero_ordre = $numero_ordre;


        $niveau->created_at = Carbon::now();

        $niveau->save();

        return $niveau;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Niveau
     */

    public static function rechercheNiveauById($id)
    {

        return   $niveau = Niveau::findOrFail($id);
    }

    /**
     * Update d'une Niveau scolaire

     * @param  string $libelle
     * @param  int $cycle_id
     * @param  int $description
     * @param  int $numero_ordre




     * @param int $id
     * @return  Niveau
     */

    public static function updateNiveau(
        $libelle,
        $cycle_id,
        $description,
        $numero_ordre,

        $id
    ) {


        return   $niveau = Niveau::findOrFail($id)->update([



            'libelle' => $libelle,
            'cycle_id' => $cycle_id,
            'description' => $description,
            'numero_ordre' => $numero_ordre,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Niveau
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteNiveau($id)
    {

        $niveau = Niveau::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($niveau) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des niveaux
     * @param  int $numero_ordre
     * @param  int $cycle_id

     *
     * @return  array
     */

    public static function getListe(


        $cycle_id = null,
        $numero_ordre = null

    ) {

        $numero_ordre_sup  = (int)$numero_ordre + 2;

        $query =  Niveau::where('etat', '!=', TypeStatus::SUPPRIME);



        if ($cycle_id != null) {

            $query->where('cycle_id', '=', $cycle_id);
        }

        if ($numero_ordre != null) {

            $query->where('numero_ordre', '>=', $numero_ordre);
        }



        return    $query->get();
    }



    /**
     * Retourne le nombre  des  années


     * @param  int $cycle_id
     * @param  int $numero_ordre


     * @return  int $total
     */

    public static function getTotal(
        $cycle_id = null,
        $numero_ordre = null





    ) {

        $query =   DB::table('niveaux')


            ->where('niveaux.etat', '!=', TypeStatus::SUPPRIME);


        if ($cycle_id != null) {

            $query->where('niveaux.cycle_id', '=', $cycle_id);
        }


        if ($numero_ordre != null) {

            $query->where('niveaux.numero_ordre', '=', $numero_ordre);
        }




        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }






    /**
     * Obtenir une cycle
     *
     */
    public function cycle()
    {


        return $this->belongsTo(Cycle::class, 'cycle_id');
    }


    function frais_ecole($annee_scolaire_id, $niveau_id)
    {
        $query = FraisEcole::query()
            ->where('niveau_id', $niveau_id ?? $this->id)
            ->where('annee_id', $annee_scolaire_id);
        return $query->get()->first();
    }
}
