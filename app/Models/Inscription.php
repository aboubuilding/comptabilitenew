<?php

namespace App\Models;

use App\Types\StatutValidation;
use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Inscription extends Model
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


        'date_inscription',
        'eleve_id',
        'cycle_id',
        'niveau_id',
        'last_niveau_id',
        'classe_id',
        'espace_id',
        'type_inscription',
        'statut_validation',
        'annee_id',
        'parent_id',
        'taux_remise',
        'motif_rejet',
        'date_validation',
        'utilisateur_id',
        'specialite_id_1',
        'specialite_id_2',
        'specialite_id_3',
        'specialite_abandonne',
        'bulletin_1',
        'bulletin_2',
        'bulletin_3',
        'dnb',
        'programme_provenance',
        'is_cantine',
        'is_bus',
        'is_livre',
        'frais_scolarite',
        'frais_assurance',
        'frais_inscription',
        'frais_cantine',
        'frais_bus',
        'frais_livre',
        'remise_scolarite',
        'frais_examen',
        'nbre_mois',
        'bus_id',
        'is_print',
        'lat',
        'lng',
        'chauffeur_id',

        'etat',

    ];



    /**
     * Ajouter une Inscription
     *


     * @param  date $date_inscription
     * @param  int $eleve_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $last_niveau_id
     * @param  int $classe_id
     * @param  int $espace_id
     * @param  int $type_inscription
     * @param  int $statut_validation
     * @param  int $annee_id
     * @param  int $parent_id
     * @param  int $taux_remise
     * @param  string $motif_rejet
     * @param  date $date_validation
     * @param  int $utilisateur_id
     * @param  int $specialite_id_1
     * @param  int $specialite_id_2
     * @param  int $specialite_id_3
     * @param  string $specialite_abandonne
     * @param  string $bulletin_1
     * @param  string $bulletin_2
     * @param  string $bulletin_3
     * @param  string $dnb
     * @param  int $programme_provenance
     * @param  int $is_cantine
     * @param  int $is_bus
     * @param  int $is_livre
     * @param  int $frais_scolarite
     * @param  int $frais_assurance
     * @param  int $frais_inscription
     * @param  int $frais_cantine
     * @param  int $frais_bus
     * @param  int $frais_livre
     * @param  int $remise_scolarite




     * @return Inscription
     */

    public static function addInscription(
        $date_inscription,
        $eleve_id,
        $cycle_id,
        $niveau_id,
        $last_niveau_id,
        $classe_id,
        $espace_id,
        $type_inscription,
        $statut_validation,
        $annee_id,
        $parent_id,
        $taux_remise,
        $motif_rejet,
        $date_validation,
        $utilisateur_id,
        $specialite_id_1,
        $specialite_id_2,
        $specialite_id_3,
        $specialite_abandonne,
        $bulletin_1,
        $bulletin_2,
        $bulletin_3,
        $dnb,
        $programme_provenance,
        $is_cantine,
        $is_bus,
        $is_livre,
        $frais_scolarite,
        $frais_assurance,
        $frais_inscription,
        $frais_cantine,
        $frais_bus,
        $frais_livre,
        $remise_scolarite

    ) {
        $inscription = new Inscription();


        $inscription->date_inscription = $date_inscription;
        $inscription->eleve_id = $eleve_id;
        $inscription->cycle_id = $cycle_id;
        $inscription->niveau_id = $niveau_id;
        $inscription->last_niveau_id = $last_niveau_id;
        $inscription->classe_id = $classe_id;
        $inscription->espace_id = $espace_id;
        $inscription->type_inscription = $type_inscription;
        $inscription->statut_validation = $statut_validation;
        $inscription->annee_id = $annee_id;
        $inscription->parent_id = $parent_id;
        $inscription->taux_remise = $taux_remise;
        $inscription->motif_rejet = $motif_rejet;
        $inscription->date_validation = $date_validation;
        $inscription->utilisateur_id = $utilisateur_id;
        $inscription->specialite_id_1 = $specialite_id_1;
        $inscription->specialite_id_2 = $specialite_id_2;
        $inscription->specialite_id_3 = $specialite_id_3;
        $inscription->specialite_abandonne = $specialite_abandonne;
        $inscription->bulletin_1 = $bulletin_1;
        $inscription->bulletin_2 = $bulletin_2;
        $inscription->bulletin_3 = $bulletin_3;
        $inscription->dnb = $dnb;
        $inscription->programme_provenance = $programme_provenance;
        $inscription->is_cantine = $is_cantine;
        $inscription->is_bus = $is_bus;
        $inscription->is_livre = $is_livre;
        $inscription->frais_scolarite = $frais_scolarite;
        $inscription->frais_assurance = $frais_assurance;
        $inscription->frais_inscription = $frais_inscription;
        $inscription->frais_cantine = $frais_cantine;
        $inscription->frais_bus = $frais_bus;
        $inscription->frais_livre = $frais_livre;
        $inscription->remise_scolarite = $remise_scolarite;




        $inscription->created_at = Carbon::now();

        $inscription->save();

        return $inscription;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  Inscription
     */

    public static function rechercheInscriptionById($id)
    {

        return   $inscription = Inscription::findOrFail($id);
    }

    /**
     * Update d'une Inscription scolaire
     *
     *
     *

     * @param  date $date_inscription
     * @param  int $eleve_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $last_niveau_id
     * @param  int $classe_id
     * @param  int $espace_id
     * @param  int $type_inscription
     * @param  int $statut_validation
     * @param  int $annee_id
     * @param  int $parent_id
     * @param  int $taux_remise
     * @param  string $motif_rejet
     * @param  date $date_validation
     * @param  int $utilisateur_id
     * @param  int $specialite_id_1
     * @param  int $specialite_id_2
     * @param  int $specialite_id_3
     * @param  string $specialite_abandonne
     * @param  string $bulletin_1
     * @param  string $bulletin_2
     * @param  string $bulletin_3
     * @param  string $dnb
     * @param  int $programme_provenance
     * @param  int $is_cantine
     * @param  int $is_bus
     * @param  int $is_livre
     * @param  int $frais_scolarite
     * @param  int $frais_assurance
     * @param  int $frais_inscription
     * @param  int $frais_cantine
     * @param  int $frais_bus
     * @param  int $frais_livre
     * @param  int $remise_scolarite


     *
     *
     * @param int $id
     * @return  Inscription
     */

    public static function updateInscription(

        $date_inscription,
        $eleve_id,
        $cycle_id,
        $niveau_id,
        $last_niveau_id,
        $classe_id,
        $espace_id,
        $type_inscription,
        $statut_validation,
        $annee_id,
        $parent_id,
        $taux_remise,
        $motif_rejet,
        $date_validation,
        $utilisateur_id,
        $specialite_id_1,
        $specialite_id_2,
        $specialite_id_3,
        $specialite_abandonne,
        $bulletin_1,
        $bulletin_2,
        $bulletin_3,
        $dnb,
        $programme_provenance,
        $is_cantine,
        $is_bus,
        $is_livre,
        $frais_scolarite,
        $frais_assurance,
        $frais_inscription,
        $frais_cantine,
        $frais_bus,
        $frais_livre,
        $remise_scolarite,






        $id
    ) {


        return   $inscription = Inscription::findOrFail($id)->update([


            'date_inscription' => $date_inscription,
            'eleve_id' => $eleve_id,
            'cycle_id' => $cycle_id,
            'niveau_id' => $niveau_id,
            'last_niveau_id' => $last_niveau_id,
            'classe_id' => $classe_id,
            'espace_id' => $espace_id,
            'type_inscription' => $type_inscription,
            'statut_validation' => $statut_validation,
            'annee_id' => $annee_id,
            'parent_id' => $parent_id,
            'taux_remise' => $taux_remise,
            'motif_rejet' => $motif_rejet,
            'date_validation' => $date_validation,
            'utilisateur_id' => $utilisateur_id,
            'specialite_id_1' => $specialite_id_1,
            'specialite_id_2' => $specialite_id_2,
            'specialite_id_3' => $specialite_id_3,
            'specialite_abandonne' => $specialite_abandonne,
            'bulletin_1' => $bulletin_1,
            'bulletin_2' => $bulletin_2,
            'bulletin_3' => $bulletin_3,
            'dnb' => $dnb,
            'programme_provenance' => $programme_provenance,
            'is_cantine' => $is_cantine,
            'is_bus' => $is_bus,
            'is_livre' => $is_livre,
            'frais_scolarite' => $frais_scolarite,
            'frais_assurance' => $frais_assurance,
            'frais_inscription' => $frais_inscription,
            'frais_cantine' => $frais_cantine,
            'frais_bus' => $frais_bus,
            'frais_livre' => $frais_livre,
            'remise_scolarite' => $remise_scolarite,

            'id' => $id,


        ]);
    }


    /**
     * Affecter remise
     *
     *
     * @param  int $taux_remise


     *
     *
     * @param int $id
     * @return  Inscription
     */

    public static function affecterRemise(
        $taux_remise,

        $id
    ) {


        return   $inscription = Inscription::findOrFail($id)->update([

            'taux_remise' => $taux_remise,


            'id' => $id,


        ]);
    }



    /**
     * Decision d'une Inscription scolaire
     *
     *
     *
     *
     * @param  int $statut_validation

     * @param  string $motif_rejet
     * @param  date $date_validation
     *@param int  $utilisateur_id
     *
     * @param int $id
     * @return  Inscription
     */

    public static function decisionInscription(

        $statut_validation,
        $motif_rejet,
        $utilisateur_id,
        $id
    ) {


        return $inscription = Inscription::findOrFail($id)->update([


            'statut_validation' => $statut_validation,
            'motif_rejet' => $motif_rejet,
            'date_validation' => Carbon::now(),
            'utilisateur_id' => $utilisateur_id,
            'id' => $id,


        ]);
    }






    /**
     * Supprimer une Inscription
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteInscription($id)
    {

        $inscription = Inscription::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($inscription) {
            return 1;
        }
        return 0;
    }










    /**
     * Retourne la liste des inscriptions
     *
     *
     * @param  int $annee_id
     * @param  int $eleve_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $classe_id
     * @param  int $espace_id
     * @param  int $type_inscription
     * @param  int $statut_validation
     * @param  int $utilisateur_id
     * @param  int $parent_id
     * @param  int $programme_provenance
     * @param  int $is_cantine
     * @param  int $is_bus
     * @param  int $is_livre


     *
     * @return  array
     */

    public static function getListe(


        $annee_id = null,
        $eleve_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,
        $espace_id = null,
        $type_inscription = null,
        $statut_validation = null,
        $utilisateur_id = null,
        $parent_id = null,
        $programme_provenance = null,
        $is_cantine = null,
        $is_bus = null,
        $is_livre = null,
        $sexe = null,
        $is_print = null

    ) {



        $query =   Inscription::join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')



            ->select('inscriptions.*')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.annee_id', '=', $annee_id)
            ->orderBy('eleves.nom', 'ASC');






        if ($eleve_id != null) {

            $query->where('eleve_id', '=', $eleve_id);
        }

        if ($cycle_id != null) {

            $query->where('cycle_id', '=', $cycle_id);
        }

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($classe_id != null) {

            $query->where('classe_id', '=', $classe_id);
        }

        if ($espace_id != null) {

            $query->where('inscriptions.espace_id', '=', $espace_id);
        }

        if ($type_inscription != null) {

            $query->where('type_inscription', '=', $type_inscription);
        }

        if ($statut_validation != null) {

            $query->where('statut_validation', '=', $statut_validation);
        }

        if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }


        if ($parent_id != null) {

            $query->where('parent_id', '=', $parent_id);
        }


        if ($programme_provenance != null) {

            $query->where('programme_provenance', '=', $programme_provenance);
        }


        if ($is_cantine != null) {

            $query->where('is_cantine', '=', $is_cantine);
        }

        if ($is_bus != null) {

            $query->where('is_bus', '=', $is_bus);
        }


        if ($is_livre != null) {

            $query->where('is_livre', '=', $is_livre);
        }


        if ($is_livre != null) {

            $query->where('is_livre', '=', $is_livre);
        }

        if ($sexe != null) {

            $query->where('eleves.sexe', '=', $sexe);
        }

        if ($is_print != null) {

            $query->where('is_print', '=', $is_print);
        }

        return    $query->get();
    }



    /**
     * Retourne le nombre  des  inscriptions
     * @param  int $annee_id
     * @param  int $eleve_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $classe_id
     * @param  int $espace_id
     * @param  int $type_inscription
     * @param  int $statut_validation
     * @param int $utilisateur_id
     * @param int $parent_id
     * @param int $programme_provenance
     * @param int $is_cantine
     * @param int $is_bus
     * @param int $is_livre
     * @param int $sexe
     *

     * @return  int $total
     */

    public static function getTotal(


        $annee_id = null,
        $eleve_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,
        $espace_id = null,
        $type_inscription = null,
        $statut_validation = null,
        $utilisateur_id = null,
        $parent_id = null,
        $programme_provenance = null,
        $is_cantine = null,
        $is_bus = null,
        $is_livre = null,
        $sexe = null



    ) {

        $query =   Inscription::join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')



            ->select('inscriptions.*')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.annee_id', '=', $annee_id)
            ->orderBy('eleves.nom', 'ASC');




        if ($eleve_id != null) {

            $query->where('eleve_id', '=', $eleve_id);
        }

        if ($cycle_id != null) {

            $query->where('cycle_id', '=', $cycle_id);
        }

        if ($niveau_id != null) {

            $query->where('niveau_id', '=', $niveau_id);
        }

        if ($classe_id != null) {

            $query->where('classe_id', '=', $classe_id);
        }

        if ($espace_id != null) {

            $query->where('inscriptions.espace_id', '=', $espace_id);
        }

        if ($type_inscription != null) {

            $query->where('type_inscription', '=', $type_inscription);
        }



        if ($statut_validation != null) {

            $query->where('statut_validation', '=', $statut_validation);
        }


        if ($utilisateur_id != null) {

            $query->where('utilisateur_id', '=', $utilisateur_id);
        }

        if ($parent_id != null) {

            $query->where('parent_id', '=', $parent_id);
        }


        if ($programme_provenance != null) {

            $query->where('programme_provenance', '=', $programme_provenance);
        }


        if ($is_cantine != null) {

            $query->where('is_cantine', '=', $is_cantine);
        }


        if ($is_bus != null) {

            $query->where('is_bus', '=', $is_bus);
        }

        if ($is_livre != null) {

            $query->where('is_livre', '=', $is_livre);
        }

        if ($sexe != null) {

            $query->where('eleves.sexe', '=', $sexe);
        }



        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }


    /**
     * Obtenir un eleve
     *
     */
    public function eleve()
    {


        return $this->belongsTo(Eleve::class, 'eleve_id');
    }


    /**
     * Obtenir un cycle
     *
     */
    public function cycle()
    {


        return $this->belongsTo(Cycle::class, 'cycle_id');
    }


    /**
     * Obtenir un niveau
     *
     */
    public function niveau()
    {


        return $this->belongsTo(Niveau::class, 'niveau_id');
    }


    /**
     * Obtenir le niveau  anterieur
     *
     */
    public function last_niveau()
    {


        return $this->belongsTo(Niveau::class, 'last_niveau_id');
    }



    /**
     * Obtenir une classe
     *
     */
    public function classe()
    {


        return $this->belongsTo(Classe::class, 'classe_id');
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
     * Obtenir un utilisateur
     *
     */
    public function utilisateur()
    {


        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }


    /**
     * Obtenir un parent
     *
     */
    public function parent()
    {


        return $this->belongsTo(ParentEleve::class, 'parent_id');
    }




    /**
     * Retourne le montant de  l ecolage d un eleve
     *
     *
     * @param  int $id

     * @return  int $total
     */

    public static function getEcolage(
        $id


    ) {

        $frais_reel = 0;

        $inscription = Inscription::rechercheInscriptionById($id);
        $taux_remise = $inscription->taux_remise;

        $niveau = Niveau::rechercheNiveauById($inscription->niveau_id);

        if ($niveau) {

            $ecolage = $niveau->frais_scolaire;

            $remise = ($ecolage * $taux_remise) / 100;

            $frais_reel = (int)($ecolage - $remise);
        }


        return $frais_reel;
    }


    /**
     * Retourne le montant de  l ecolage d un espace  pour une annee
     *
     *
     * @param  int $id
     * @param  int $annee_id

     * @return  int $total
     */

    public static function getEcolageEspace(
        $id,
        $annee_id


    ) {

        $total = 0;
        $eleves  = Inscription::getListe($annee_id, null, null, null, null, $id,);

        foreach ($eleves as $eleve) {
            $total += Inscription::getEcolage($eleve->id);
        }


        return   $total;
    }


    public static function getEcolageParent(
        $id,
        $annee_id


    ) {

        $total = 0;
        $eleves  = Inscription::getListe($annee_id, null, null, null, null, null, null, null, null,  $id);

        foreach ($eleves as $eleve) {
            $total += Inscription::getEcolage($eleve->id);
        }


        return   $total;
    }




    /**
     * Modification de la classe
     *
     *
     *

     * @param  int $classe_id
     * @param  int $niveau_id



     *
     *
     * @param int $id
     * @return  boolean
     */

    public static function   modifierInscription(


        $classe_id,
        $niveau_id,

        $id
    ) {


        return   $inscription = Inscription::findOrFail($id)->update([



            'classe_id' => $classe_id,
            'niveau_id' => $niveau_id,



            'id' => $id,


        ]);
    }



    public static function getParents(
        $id,
        $annee_id



    ) {


        $espace_id  = Inscription::rechercheInscriptionById($id)->espace_id;

        $parents =  ParentEleve::getListe($annee_id, $espace_id);

        if ($parents) {

            return $parents;
        }
        return null;
    }


    /**
     * Retourne le chiffre d affaire previsionnel  par rapport a des critères
     * @param  int $annee_id
     * @param  int $eleve_id
     * @param  int $cycle_id
     * @param  int $niveau_id
     * @param  int $classe_id
     * @param  int $espace_id
     * @param  int $type_inscription
     * @param  int $statut_validation
     * @param int $utilisateur_id
     * @param int $parent_id
     * @param int $programme_provenance
     * @param int $is_cantine
     * @param int $is_bus
     * @param int $is_livre
     * @param int $sexe
     *

     * @return  int $chiffre_affaire
     */






    public static function getChiffreAffaire(

        $annee_id = null,
        $eleve_id = null,
        $cycle_id = null,
        $niveau_id = null,
        $classe_id = null,
        $espace_id = null,
        $type_inscription = null,
        $statut_validation = null,
        $utilisateur_id = null,
        $parent_id = null,
        $programme_provenance = null,
        $is_cantine = null,
        $is_bus = null,
        $is_livre = null,
        $sexe = null



    ) {

        $chiffre_affaire = 0;



        $eleves  = Inscription::getListe($annee_id);

        foreach ($eleves as $eleve) {
            $chiffre_affaire += Inscription::getCAbyEleve($eleve->id);
        }




        return $chiffre_affaire;
    }





    /**
     * Retourne le chiffre d affaire par  un eleve
     *
     *
     * @param  int $id

     * @return  int $total
     */

    public static function getCAbyEleve(
        $id


    ) {

        $chiffre_affaire = 0;

        $inscription = Inscription::rechercheInscriptionById($id);


        if ($inscription) {


            $frais_scolarite = (float)$inscription->frais_scolarite;
            $frais_inscription = (float)$inscription->frais_inscription;
            $frais_cantine = (float)$inscription->frais_cantine;
            $frais_inscription = (float)$inscription->frais_inscription;
            $frais_bus = (float)$inscription->frais_bus;
            $frais_livre = (float)$inscription->frais_livre;

            $chiffre_affaire = $frais_scolarite +  $frais_inscription  +  $frais_cantine  +  $frais_inscription  +  $frais_bus  +   $frais_livre;
        }



        return $chiffre_affaire;
    }





    /**
     * Update d'une Inscription scolaire
     *
     *
     *



     * @param  int $is_cantine
     * @param  int $is_bus
     * @param  int $is_livre
     * @param  int $frais_scolarite
     * @param  int $frais_assurance
     * @param  int $frais_inscription
     * @param  int $frais_cantine
     * @param  int $frais_bus
     * @param  int $frais_livre
     * @param  int $remise_scolarite
     * @param int $nbre_mois
    * @param int  $bus_id
     *
     *
     * @param int $id
     * @return  boolean
     */

    public static function updateInscriptionSouscription(


        $is_cantine,
        $is_bus,
        $is_livre,
        $frais_scolarite,
        $frais_assurance,
        $frais_inscription,
        $frais_cantine,
        $frais_bus,
        $frais_livre,
        $remise_scolarite,
        $nbre_mois,
        $bus_id,

        $id
    ) {


        return   $inscription = Inscription::findOrFail($id)->update([



            'is_cantine' => $is_cantine,
            'is_bus' => $is_bus,
            'is_livre' => $is_livre,
            'frais_scolarite' => $frais_scolarite,
            'frais_assurance' => $frais_assurance,
            'frais_inscription' => $frais_inscription,
            'frais_cantine' => $frais_cantine,
            'frais_bus' => $frais_bus,
            'frais_livre' => $frais_livre,
            'remise_scolarite' => $remise_scolarite,
            'nbre_mois' => $nbre_mois,
            'bus_id' => $bus_id,

            'id' => $id,


        ]);
    }




    /**
     * Retourne le chiffre d affaire pour un cycle , ou un niveau
     *
     *


     * @return  int $total
     */

    public static function getScolaritePrevisionnel(
        $annee_id = null,
        $cycle_id  = null,
        $niveau_id  = null,
        $classe_id  = null




    ) {



        $query =  DB::table('inscriptions')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.statut_validation', '=', StatutValidation::VALIDE);

        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }






        $total = $query->SUM('inscriptions.frais_scolarite');

        if ($total) {

            return   $total;
        }

        return 0;
    }


    /**
     * Retourne le chiffre d affaire pour un cycle , ou un niveau
     *
     *
     * @param  int $id

     * @return  int $total
     */

    public static function getCantinePrevisionnel(
        $annee_id = null,
        $cycle_id  = null,
        $niveau_id  = null,
        $classe_id  = null,





    ) {

        $query =  DB::table('inscriptions')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.statut_validation', '=', StatutValidation::VALIDE);

        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }





        $total = $query->SUM('inscriptions.frais_cantine');

        if ($total) {

            return   $total;
        }

        return 0;
    }


    /**
     * Retourne le chiffre d affaire du bus  pour un cycle , ou un niveau
     *
     *
     * @param  int $id

     * @return  int $total
     */

    public static function getBusPrevisionnel(
        $annee_id = null,
        $cycle_id  = null,
        $niveau_id  = null,
        $classe_id  = null




    ) {


        $query =  DB::table('inscriptions')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.statut_validation', '=', StatutValidation::VALIDE);

        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }





        $total = $query->SUM('inscriptions.frais_bus');

        if ($total) {

            return   $total;
        }

        return 0;
    }


    /**
     * Retourne le chiffre d affaire du bus  pour un cycle , ou un niveau
     *
     *
     * @param  int $id

     * @return  int $total
     */

    public static function getLivrePrevisionnel(
        $annee_id = null,
        $cycle_id  = null,
        $niveau_id  = null,
        $classe_id  = null




    ) {



        $query =  DB::table('inscriptions')
            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->where('inscriptions.statut_validation', '=', StatutValidation::VALIDE);


        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }


        if ($cycle_id != null) {

            $query->where('inscriptions.cycle_id', '=', $cycle_id);
        }


        if ($niveau_id != null) {

            $query->where('inscriptions.niveau_id', '=', $niveau_id);
        }


        if ($classe_id != null) {

            $query->where('inscriptions.classe_id', '=', $classe_id);
        }




        $total = $query->SUM('inscriptions.frais_livre');

        if ($total) {

            return   $total;
        }

        return 0;
    }



    /**
     * Retourne la liste des  eleves en classe d examen
     *
     *
     * @param  int $annee_id

     *  @return  array
     */

    public static function getListeExamen(

        $annee_id = null



    ) {
        $niveau_examen = [13, 15, 16];

        $query =  Inscription::select(
            'inscriptions.id as inscription_id',
            'eleves.nom as nom_eleve',
            'eleves.prenom as prenom_eleve',
            'niveaux.libelle as niveau_libelle',
            'niveaux.id as niveau_id'
        )

            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->join('niveaux', 'inscriptions.niveau_id', '=', 'niveaux.id')

            ->where('inscriptions.etat', '!=', TypeStatus::SUPPRIME)
            ->whereIn('niveaux.id', $niveau_examen);


        if ($annee_id != null) {

            $query->where('inscriptions.annee_id', '=', $annee_id);
        }

        return    $query->get();
    }



    /**


     * @param  int $frais_examen


     *
     *
     * @param int $id
     * @return  boolean
     */

    public static function updateFraisExamen(



        $frais_examen,

        $id
    ) {


        return   $inscription = Inscription::findOrFail($id)->update([




            'frais_examen' => $frais_examen,


            'id' => $id,


        ]);
    }

    /**
     * Obtenir une annee
     *
     */
    public function annee()
    {


        return $this->belongsTo(Annee::class, 'annee_id');
    }

   

   
}
