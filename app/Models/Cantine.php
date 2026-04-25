<?php

namespace App\Models;

use App\Types\TypeStatus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Cantine extends Model
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
        'type_offre',
        'annee_id',
        'inscription_id',



        'etat',

    ];



    /**
     * Ajouter un Cantine
     *

     * @param  date $date_souscription
     * @param  string $montant_annuel_prevu
     * @param  string $type_offre

     * @param  int $annee_id
     * @param  int $inscription_id




     * @return Cantine
     */

    public static function addCantine(
        $date_souscription,
        $montant_annuel_prevu,
        $type_offre,
        $annee_id,
        $inscription_id

    )
    {
        $Cantine = new Cantine();


        $Cantine->date_souscription = $date_souscription;
        $Cantine->montant_annuel_prevu = $montant_annuel_prevu;
        $Cantine->type_offre = $type_offre;


        $Cantine->annee_id = $annee_id;
        $Cantine->inscription_id = $inscription_id;

        $Cantine->created_at = Carbon::now();

        $Cantine->save();

        return $Cantine;
    }

    /**
     * Affichage d'un Cantine
     * @param int $id
     * @return  Cantine
     */

    public static function rechercheCantineById($id)
    {

        return   $Cantine = Cantine::findOrFail($id);
    }

    /**
     * Update d'une Cantine scolaire

    * @param  date $date_souscription
     * @param  string $montant_annuel_prevu
     * @param  string $type_offre


     * @param  int $annee_id
     * @param  int $inscription_id


     * @param int $id
     * @return  Cantine
     */

    public static function updateCantine(
        $date_souscription,
        $montant_annuel_prevu,
        $type_offre,

        $annee_id,
        $inscription_id,


        $id)
    {


        return   $Cantine = Cantine::findOrFail($id)->update([



            'date_souscription' => $date_souscription,
            'montant_annuel_prevu' => $montant_annuel_prevu,
            'type_offre' => $type_offre,

            'annee_id' => $annee_id,
            'inscription_id' => $inscription_id,



            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Cantine
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteCantine($id)
    {

        $Cantine = Cantine::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($Cantine) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des Cantines


     * @param  int $annee_id

     * @param  int $inscription_id
     * @param  int $type_offre

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $inscription_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null


    ) {



        $query =  Cantine:: select('cantines.inscription_id', 'cantines.date_souscription', 'cantines.montant_annuel_prevu', 'eleves.nom as nom_eleve', 'eleves.prenom as prenom_eleve', 'cantines.type_offre as offre','cycles.libelle as libelle_cycle',
        'niveaux.libelle as niveau_libelle')

        ->join('inscriptions','cantines.inscription_id','=','inscriptions.id')
        ->join('eleves','inscriptions.eleve_id','=','eleves.id')
        ->join('cycles','inscriptions.cycle_id','=','cycles.id')
        ->join('niveaux','inscriptions.niveau_id','=','niveaux.id')


        ->where('cantines.etat', '!=', TypeStatus::SUPPRIME)


        ;

        if ($annee_id != null) {

            $query->where('cantines.annee_id', '=', $annee_id);
        }


        if ($inscription_id != null) {

            $query->where('cantines.inscription_id', '=', $inscription_id);
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





        return    $query->get();
    }



    /**
     * Retourne le nombre  des  Cantines


   * @param  int $annee_id
     * @param  int $fournisseur_id
     * @param  int $inscription_id
     * @param  int $statut_livraison


     * @return  int $total
     */

    public static function getTotal(
        $annee_id = null,
        $inscription_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null





    ) {

        $query =  Cantine:: select('inscriptions.id as inscription_id', 'eleves.nom as nom_eleve', 'eleves.prenom as prenom_eleve', 'cantines.type_offre as offre','cycles.libelle as libelle_cycle',
        'niveaux.libelle as niveau_libelle', 'cantines.')

        ->join('inscriptions','cantines.inscription_id','=','inscriptions.id')
        ->join('eleves','inscriptions.eleve_id','=','eleves.id')
        ->join('cycles','inscriptions.cycle_id','=','cycles.id')
        ->join('niveaux','inscriptions.niveau_id','=','niveaux.id')



        ->where('cantines.etat', '!=', TypeStatus::SUPPRIME);


        if ($inscription_id != null) {

            $query->where('cantines.inscription_id', '=', $inscription_id);
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





}
