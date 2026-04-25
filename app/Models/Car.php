<?php

namespace App\Models;

use App\Types\TypeStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Car extends Model
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


        'date_souscription',
        'montant_annuel_prevu',
        'destination',
        'adresse_map',
        'annee_id',
        'inscription_id',
        'ligne_id',
        'zone_id',



        'etat',

    ];



    /**
     * Ajouter un Car
     *

     * @param  date $date_souscription
     * @param  float $montant_annuel_prevu
     * @param  string $destination

     * @param  string $adresse_map
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $ligne_id
     * @param  int $zone_id




     * @return Car
     */

    public static function addCar(
        $date_souscription,
        $montant_annuel_prevu,
        $destination,
        $adresse_map,
        $annee_id,
        $inscription_id,
        $ligne_id,
        $zone_id

    )
    {
        $car = new Car();


        $car->date_souscription = $date_souscription;
        $car->montant_annuel_prevu = $montant_annuel_prevu;
        $car->destination = $destination;
        $car->adresse_map = $adresse_map;


        $car->annee_id = $annee_id;
        $car->inscription_id = $inscription_id;
        $car->ligne_id = $ligne_id;
        $car->zone_id = $zone_id;

        $car->created_at = Carbon::now();

        $car->save();

        return $car;
    }

    /**
     * Affichage d'un Car
     * @param int $id
     * @return  Car
     */

    public static function rechercheCarById($id)
    {

        return   $car = Car::findOrFail($id);
    }

    /**
     * Update d'une Car scolaire

     * @param  date $date_souscription
     * @param  float $montant_annuel_prevu
     * @param  string $destination

     * @param  string $adresse_map
     * @param  int $annee_id
     * @param  int $inscription_id
     * @param  int $ligne_id
     * @param  int $zone_id



     * @param int $id
     * @return  Car
     */

    public static function updateCar(
        $date_souscription,
        $montant_annuel_prevu,
        $destination,
        $adresse_map,
        $annee_id,
        $inscription_id,
        $ligne_id,
        $zone_id,


        $id)
    {


        return   $car = Car::findOrFail($id)->update([



            'date_souscription' => $date_souscription,
            'montant_annuel_prevu' => $montant_annuel_prevu,
            'destination' => $destination,

            'adresse_map' => $adresse_map,
            'annee_id' => $annee_id,
            'inscription_id' => $inscription_id,
            'ligne_id' => $ligne_id,
            'zone_id' => $zone_id,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Car
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCar($id)
    {

        $car = Car::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($car) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Cars


     * @param  int $annee_id

     * @param  int $inscription_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $classe_id
     * @param  int $zone_id
     * @param  int $ligne_id

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $inscription_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,
        $ligne_id = null,
        $zone_id = null,


    ) {



        $query =  Car:: select('inscriptions.id as inscription_id', 'eleves.nom as nom_eleve', 'eleves.prenom as prenom_eleve', 'lignes.libelle as ligne', 'zones.libelle as zone','cycles.libelle as libelle_cycle',
        'niveaux.libelle as niveau_libelle')

        ->join('inscriptions','cars.inscription_id','=','inscriptions.id')
        ->join('eleves','inscriptions.eleve_id','=','eleves.id')
        ->join('cycles','inscriptions.cycle_id','=','cycles.id')
        ->join('niveaux','inscriptions.niveau_id','=','niveaux.id')
        ->join('lignes','lignes.id','=','cars.ligne_id')
        ->join('zones','zones.id','=','cars.zone_id')


        ->where('cars.etat', '!=', TypeStatus::SUPPRIME)


        ;

        if ($annee_id != null) {

            $query->where('cars.annee_id', '=', $annee_id);
        }


        if ($inscription_id != null) {

            $query->where('cars.inscription_id', '=', $inscription_id);
        }

        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }


        if ($cycle_id!= null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($ligne_id!= null) {

            $query->where('cars.ligne_id', '=', $ligne_id);
        }


        if ($zone_id!= null) {

            $query->where('cars.zone_id', '=', $zone_id);
        }






        return    $query->get();
    }



    /**
     * Retourne le nombre  des  Cars


  * @param  int $annee_id

     * @param  int $inscription_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $classe_id
     * @param  int $zone_id
     * @param  int $ligne_id


     * @return  int $total
     */

    public static function getTotal(
        $annee_id = null,
        $inscription_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,
        $ligne_id = null,
        $zone_id = null,





    ) {

        $query =  Car:: select('inscriptions.id as inscription_id', 'eleves.nom as nom_eleve', 'eleves.prenom as prenom_eleve', 'lignes.libelle as ligne', 'zones.libelle as zone','cycles.libelle as libelle_cycle',
        'niveaux.libelle as niveau_libelle')

        ->join('inscriptions','cars.inscription_id','=','inscriptions.id')
        ->join('eleves','inscriptions.eleve_id','=','eleves.id')
        ->join('cycles','inscriptions.cycle_id','=','cycles.id')
        ->join('niveaux','inscriptions.niveau_id','=','niveaux.id')
        ->join('lignes','lignes.id','=','cars.ligne_id')
        ->join('zones','zones.id','=','cars.zone_id')


        ->where('cars.etat', '!=', TypeStatus::SUPPRIME);

        if ($annee_id != null) {

            $query->where('cars.annee_id', '=', $annee_id);
        }


        if ($inscription_id != null) {

            $query->where('cars.inscription_id', '=', $inscription_id);
        }

        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }


        if ($cycle_id!= null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($ligne_id!= null) {

            $query->where('cars.ligne_id', '=', $ligne_id);
        }


        if ($zone_id!= null) {

            $query->where('cars.zone_id', '=', $zone_id);
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
     * Obtenir un fournisseur
     *
     */
    public function inscription()
    {


        return $this->belongsTo(Inscription::class, 'inscription_id');
    }



    /**
     * Obtenir un fournisseur
     *
     */
    public function ligne()
    {


        return $this->belongsTo(Ligne::class, 'ligne_id');
    }


     /**
     * Obtenir un fournisseur
     *
     */
    public function zone()
    {


        return $this->belongsTo(Zone::class, 'zone_id');
    }





}
