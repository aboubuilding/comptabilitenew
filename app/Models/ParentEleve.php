<?php

namespace App\Models;

use App\Types\TypeStatus;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ParentEleve extends Model
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


        'nom_parent',
        'prenom_parent',
        'telephone',
        'profession',
        'espace_id',
        'is_principal',
        'role',
        'annee_id',
        'nationalite_id',
        'quartier',
        'adresse',
        'whatsapp',
        'email',
        'etat',

    ];



    /**
     * Ajouter une ParentEleve
     *

     * @param  string $nom_parent
     * @param  string $prenom_parent
     * @param  string $telephone
     *  @param  string $whatsapp
     * @param  string $profession
     * @param  int $espace_id
     * @param  int $is_principal
     * @param  int $role
     * @param int $annee_id
     * @param int $nationalite_id
     * @param string $quartier
     * @param string $adresse
     * @param string $email
     *
     * @return ParentEleve
     */

    public static function addParentEleve(
        $nom_parent,
        $prenom_parent,
        $telephone,
        $profession,
        $whatsapp,
        $espace_id,
        $is_principal,
        $role,
        $annee_id,
        $nationalite_id,
        $quartier,
        $adresse,
        $email

    ) {
        $parenteleve = new ParentEleve();


        $parenteleve->nom_parent = $nom_parent;
        $parenteleve->prenom_parent = $prenom_parent;
        $parenteleve->telephone = $telephone;
        $parenteleve->whatsapp = $whatsapp;
        $parenteleve->profession = $profession;
        $parenteleve->espace_id = $espace_id;
        $parenteleve->is_principal = $is_principal;
        $parenteleve->role = $role;
        $parenteleve->annee_id = $annee_id;
        $parenteleve->nationalite_id = $nationalite_id;
        $parenteleve->quartier = $quartier;
        $parenteleve->adresse = $adresse;
        $parenteleve->email = $email;
        $parenteleve->created_at = Carbon::now();

        $parenteleve->save();

        return $parenteleve;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  ParentEleve
     */

    public static function rechercheParentEleveById($id)
    {

        return   $parenteleve = ParentEleve::findOrFail($id);
    }

    /**
     * Update d'une ParentEleve scolaire
     * @param  string $nom_parent
     * @param  string $prenom_parent
     * @param  string $telephone
     * @param  string $whatsapp
     * @param  string $profession
     * @param  int $espace_id
     * @param  int $is_principal
     * @param  int $role
     * @param  int $annee_id
     * @param int $nationalite_id
     * @param string $quartier
     * @param string $adresse
     * @param string $email
     *
     *
     * @param int $id
     * @return  ParentEleve
     */

    public static function updateParentEleve(
        $nom_parent,
        $prenom_parent,
        $telephone,
        $whatsapp,
        $profession,
        $espace_id,
        $is_principal,
        $role,
        $annee_id,
        $nationalite_id,
        $quartier,
        $adresse,
        $email,
        $id
    ) {

        return   $parenteleve = ParentEleve::findOrFail($id)->update([

            'nom_parent' => $nom_parent,
            'prenom_parent' => $prenom_parent,
            'telephone' => $telephone,
            'profession' => $profession,
            'whatsapp' => $whatsapp,
            'espace_id' => $espace_id,
            'is_principal' => $is_principal,
            'role' => $role,
            'annee_id' => $annee_id,
            'nationalite_id' => $nationalite_id,
            'quartier' => $quartier,
            'adresse' => $adresse,
            'email' => $email,
            'id' => $id,

        ]);
    }




    /**
     * Supprimer une ParentEleve
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteParentEleve($id)
    {

        $parenteleve = ParentEleve::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($parenteleve) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des années
     * @param  int $annee_id
     * @param  int $espace_id
     * @param  int $is_principal
     * @param  int $role

     *
     * @return  array
     */

    public static function getListe(

        $annee_id = null,
        $espace_id = null,
        $is_principal = null,
        $role = null

    ) {

        $query =  ParentEleve::where('etat', '!=', TypeStatus::SUPPRIME);


        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }

        if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }

        if ($role != null) {

            $query->where('role', '=', $role);
        }

        if ($is_principal != null) {

            $query->where('is_principal', '=', $is_principal);
        }

        return    $query->get();
    }



    /**
     * Retourne le nombre  des  années
     *
     * @param int $annee_id
     * @param  int $espace_id
     * @param  int $is_principal
     * @param  int $role
     *
     *
     *
     * @return  int $total
     */

    public static function getTotal(

        $annee_id = null,
        $espace_id = null,
        $is_principal = null,
        $role = null

    ) {

        $query =   DB::table('parent_eleves')

            ->where('parent_eleves.etat', '!=', TypeStatus::SUPPRIME);

        if ($annee_id != null) {

            $query->where('annee_id', '=', $annee_id);
        }


        if ($espace_id != null) {

            $query->where('espace_id', '=', $espace_id);
        }


        if ($role != null) {

            $query->where('role', '=', $role);
        }

        if ($is_principal != null) {

            $query->where('is_principal', '=', $is_principal);
        }

        $total = $query->count();

        if ($total) {

            return   $total;
        }

        return 0;
    }
}
