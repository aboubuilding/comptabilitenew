<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Eleve extends Model
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


        'matricule',
        'nom',
        'prenom',
        'prenom_usuel',
        'ecole_provenance',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite_id',
        'espace_id',
        'nom_medecin',
        'personne_prevenir',
        'photo',
        'carte_identite',
        'naissance',
        'groupe_id',
        'certificat_medical',
        'vaccin_1',
        'vaccin_2',
        'vaccin_3',
        'vaccin_4',
        'vaccin_5',
        'numero_medecin',
        'numero_personne_prevenir',
        'lien_parente_personne',
        'naissance_eleve',
        'allergie',



        'etat',

    ];



    /**
     * Ajouter une Eleve
     *

     * @param  string $matricule
     * @param  string $nom
     * @param  string $prenom
     * @param  string $prenom_usuel
     * @param  date $date_naissance
     * @param  string $lieu_naissance
     * @param  string $sexe
     * @param  int $nationalite_id
     * @param  int $espace_id
     * @param  string $nom_medecin
     * @param  string $personne_prevenir
     * @param  string $photo
     * @param  string $groupe_id
     * @param  string $certificat_medical
     * @param  string $vaccin_1
     * @param  string $vaccin_2
     * @param  string $vaccin_3
     * @param  string $vaccin_4
     * @param  string $vaccin_5
     *
     * @param  string $numero_medecin
     * @param  string $numero_personne_prevenir
     * @param  int $lien_parente_personne
     * @param  string $naissance_eleve
     * @param  string $allergie






     * @return Eleve
     */

    public static function addEleve(
        $matricule,
        $nom,
        $prenom,
        $prenom_usuel,
        $ecole_provenance,
        $date_naissance,
        $lieu_naissance,
        $sexe,
        $nationalite_id,
        $espace_id,
        $nom_medecin,
        $personne_prevenir,
        $photo,
        $carte_identite,
        $naissance,
        $groupe_id,
        $certificat_medical,
        $vaccin_1,
        $vaccin_2,
        $vaccin_3,
        $vaccin_4,
        $vaccin_5,

        $numero_medecin,
        $numero_personne_prevenir,
        $lien_parente_personne,
        $naissance_eleve,
        $allergie


    )
    {
        $eleve = new Eleve();


        $eleve->matricule = $matricule;
        $eleve->nom = $nom;
        $eleve->prenom = $prenom;
        $eleve->prenom_usuel = $prenom_usuel;
        $eleve->ecole_provenance = $ecole_provenance;
        $eleve->date_naissance = $date_naissance;
        $eleve->lieu_naissance = $lieu_naissance;
        $eleve->sexe = $sexe;
        $eleve->nationalite_id = $nationalite_id;
        $eleve->espace_id = $espace_id;
        $eleve->nom_medecin = $nom_medecin;
        $eleve->personne_prevenir = $personne_prevenir;
        $eleve->photo = $photo;
        $eleve->carte_identite = $carte_identite;
        $eleve->naissance = $naissance;
        $eleve->groupe_id = $groupe_id;
        $eleve->certificat_medical = $certificat_medical;
        $eleve->vaccin_1 = $vaccin_1;
        $eleve->vaccin_2 = $vaccin_2;
        $eleve->vaccin_3 = $vaccin_3;
        $eleve->vaccin_4 = $vaccin_4;
        $eleve->vaccin_5 = $vaccin_5;
        $eleve->numero_medecin = $numero_medecin;
        $eleve->numero_personne_prevenir = $numero_personne_prevenir;
        $eleve->lien_parente_personne = $lien_parente_personne;
        $eleve->naissance_eleve = $naissance_eleve;
        $eleve->allergie = $allergie;




        $eleve->created_at = Carbon::now();

        $eleve->save();

        return $eleve;
    }

    /**
     * Affichage d'un eleve
     * @param int $id
     * @return  Eleve
     */

    public static function rechercheEleveById($id)
    {

        return   $eleve = Eleve::findOrFail($id);
    }

    /**
     * Update d'une Eleve scolaire
     *
     *
  * @param  string $matricule
     * @param  string $nom
     * @param  string $prenom
     * @param  string $prenom_usuel
     * @param  date $date_naissance
     * @param  string $lieu_naissance
     * @param  string $sexe
     * @param  int $nationalite_id
     * @param  int $espace_id
     * @param  string $nom_medecin
     * @param  string $personne_prevenir
     * @param  string $photo
     * @param  string $carte_identite
     * @param  string $naissance
     * @param  string $groupe_id
     * @param  string $certificat_medical
     * @param  string $vaccin_1
     * @param  string $vaccin_2
     * @param  string $vaccin_3
     * @param  string $vaccin_4
     * @param  string $vaccin_5
     *
     *
     * @param  string $numero_medecin
     * @param  string $numero_personne_prevenir
     * @param  int $lien_parente_personne
     * @param  string $naissance_eleve
     * @param  string $allergie
     *
 * @param int $id
     * @return  Eleve
     */

    public static function updateEleve(
        $matricule,
        $nom,
        $prenom,
        $prenom_usuel,
        $ecole_provenance,
        $date_naissance,
        $lieu_naissance,
        $sexe,
        $nationalite_id,
        $espace_id,
        $nom_medecin,
        $personne_prevenir,
        $photo,
        $carte_identite,
        $naissance,
        $groupe_id,
        $certificat_medical,
        $vaccin_1,
        $vaccin_2,
        $vaccin_3,
        $vaccin_4,
        $vaccin_5,

         $numero_medecin,
        $numero_personne_prevenir,
        $lien_parente_personne,
        $naissance_eleve,
        $allergie,

        $id)
    {


        return   $eleve = Eleve::findOrFail($id)->update([



            'matricule' => $matricule,
            'nom' => $nom,
            'prenom' => $prenom,
            'prenom_usuel' => $prenom_usuel,
            'ecole_provenance' => $ecole_provenance,
            'date_naissance' => $date_naissance,
            'lieu_naissance' => $lieu_naissance,
            'sexe' => $sexe,
            'nationalite_id' => $nationalite_id,
            'espace_id' => $espace_id,
            'nom_medecin' => $nom_medecin,
            'personne_prevenir' => $personne_prevenir,
            'photo' => $photo,
            'carte_identite' => $carte_identite,
            'naissance' => $naissance,
            'groupe_id' => $groupe_id,
            'certificat_medical' => $certificat_medical,
            'vaccin_1' => $vaccin_1,
            'vaccin_2' => $vaccin_2,
            'vaccin_3' => $vaccin_3,
            'vaccin_4' => $vaccin_4,
            'vaccin_5' => $vaccin_5,


             'numero_medecin' => $numero_medecin,
            'numero_personne_prevenir' => $numero_personne_prevenir,
            'lien_parente_personne' => $lien_parente_personne,
            'naissance_eleve' => $naissance_eleve,
            'allergie' => $allergie,


            'id' => $id,


        ]);
    }




    /**
     * Supprimer une Eleve
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteEleve($id)
    {

        $eleve = Eleve::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($eleve) {
            return 1;
        }
        return 0;
    }



     /**
     * Retourne la liste des eleves
     *
     *
     * @param  int $espace_id
     * @param  int $groupe_id
     * @param  int $nationalite_id
     * @param  int $sexe

     *
     * @return  array
     */

     public static function getListe(

        $espace_id = null,
        $groupe_id = null,
        $nationalite_id = null,
        $sexe = null


    ) {

        $query =  Eleve::where('etat', '!=', TypeStatus::SUPPRIME)
         ;

        if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }

        if ($groupe_id != null) {

            $query->where('groupe_id', '=', $groupe_id);
        }

        if ($nationalite_id != null) {

            $query->where('nationalite_id', '=', $nationalite_id);
        }

         if ($sexe != null) {

            $query->where('sexe', '=', $sexe);
        }




        return    $query->get();
    }



    /**
     * Retourne le nombre  des  années


     * @param  int $espace_id
     * @param  int $groupe_id
     * @param  int $nationalite_id
     * @param  int $sexe


     * @return  int $total
     */

    public static function getTotal(

       $espace_id = null,
        $groupe_id = null,
        $nationalite_id = null,
        $sexe = null


    ) {

        $query =   DB::table('eleves')


            ->where('eleves.etat', '!=', TypeStatus::SUPPRIME);


            if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }

        if ($groupe_id != null) {

            $query->where('groupe_id', '=', $groupe_id);
        }

        if ($nationalite_id != null) {

            $query->where('nationalite_id', '=', $nationalite_id);
        }

         if ($sexe != null) {

            $query->where('sexe', '=', $sexe);
        }


        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }


    /**
     * Obtenir un groupe
     *
     */

    /**
     * Obtenir une nationalite
     *
     */
    public function nationalite()
    {


        return $this->belongsTo(Nationalite::class, 'nationalite_id');
    }


    /**
     * Obtenir un espace
     *
     */
    public function espace()
    {


        return $this->belongsTo(Espace::class, 'espace_id');
    }


    /**
     * Génère un numéro matricule unique.
     *
     * @return string
     */
    public static function genererMatricule()
    {
        $prefix = date('Ymd'); // Préfixe basé sur la date actuelle
        $uniqueId = strtoupper(bin2hex(random_bytes(3))); // Génère une chaîne hexadécimale de 6 caractères

        return $prefix . '-' . $uniqueId;
    }


    /**
     * Mise a jour des données d identite  de l eleve
     *
     *

     * @param  string $nom
     * @param  string $prenom
     * @param  date $date_naissance
     * @param  string $lieu_naissance
     * @param  string $sexe
     * @param  int $nationalite_id
     *
     * @param int $id
     * @return  Eleve
     */

    public static function modifierEleve(

        $nom,
        $prenom,
        $date_naissance,
        $lieu_naissance,
        $sexe,
        $nationalite_id,


        $id)
    {


        return   $eleve = Eleve::findOrFail($id)->update([


            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => $date_naissance,
            'lieu_naissance' => $lieu_naissance,
            'sexe' => $sexe,
            'nationalite_id' => $nationalite_id,

            'id' => $id,


        ]);
    }






}
