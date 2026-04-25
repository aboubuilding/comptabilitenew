<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Types\Role;
use App\Types\TypeStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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


        'nom',
        'prenom',
        'login',
        'email',
        'mot_passe',
        'role',
        'photo',
        'etat',

    ];



    /**
     * Ajouter une User
     *

     * @param  string $nom
     * @param  string $prenom
     * @param  string $login
     * @param  string $mot_passe
     * @param  int $role
     * @param string $email
     * @param string $photo




     * @return User
     */

    public static function addUser(
        $nom,
        $prenom,
        $login,
        $mot_passe,
        $role,
        $email,
        $photo

    ) {
        $user = new User();


        $user->nom = $nom;
        $user->prenom = $prenom;
        $user->login = $login;
        $user->mot_passe = $mot_passe;
        $user->role = $role;
        $user->email = $email;
        $user->photo = $photo;


        $user->created_at = Carbon::now();

        $user->save();

        return $user;
    }

    /**
     * Affichage d'une année scolaire
     * @param int $id
     * @return  User
     */

    public static function rechercheUserById($id)
    {

        return $user = User::findOrFail($id);
    }

    /**
     * Update d'une User scolaire

     * @param  string $nom
     * @param  date $prenom
     * @param  date $login
     * @param  int $role
     * @param string $email
     * @param string $photo


     * @param int $id
     * @return  User
     */

    public static function updateUser(
        $nom,
        $prenom,
        $login,
        $role,
        $email,
        $photo,


        $id
    ) {


        return $user = User::findOrFail($id)->update([



            'nom' => $nom,
            'prenom' => $prenom,
            'login' => $login,
            'role' => $role,
            'email' => $email,
            'photo' => $photo,

            'id' => $id,


        ]);
    }




    /**
     * Supprimer une User
     *
     * @param int $id
     * @return  boolean
     */

    public static function deleteUser($id)
    {

        $user = User::findOrFail($id)->update([
            'etat' => TypeStatus::SUPPRIME

        ]);

        if ($user) {
            return 1;
        }
        return 0;
    }



    /**
     * Retourne la liste des années
     * @param  int $role



     *
     * @return  array
     */

    public static function getListe(

        $role = null



    ) {

        $query = User::where('etat', '!=', TypeStatus::SUPPRIME)
        ;




        if ($role != null) {

            $query->where('role', '=', $role);
        }



        return $query->get();
    }



    /**
     * Retourne le nombre  des  années


     * @param  int $role


     * @return  int $total
     */

    public static function getTotal(

        $role = null
    ) {

        $query = DB::table('Users')


            ->where('Users.etat', '!=', TypeStatus::SUPPRIME);


        if ($role != null) {

            $query->where('role', '=', $role);
        }



        $total = $query->count();

        if ($total) {

            return $total;
        }

        return 0;
    }






    /**
     * Verifier si l'User   existe deja
     *


     * @param  string $login
     * @param  string $mot_passe

     * @return  boolean
     */

    public static function isExiste($login, $mot_passe)
    {
        $users = User::getTotal();

        if (!$users) {

            User::genererUser();
        }


        $user = User::where('etat', '!=', TypeStatus::SUPPRIME)


            ->where('login', '=', $login)

            ->where('mot_passe', '=', $mot_passe)


            ->first();


        if ($user) {



            return 1;
        }

        return 0;
    }


    /**
     * Verification de l' authenttification '


     * @param  string $login
     * @param  string $mot_passe



     * @return  array
     */

    public static function isAuthenticate($login, $mot_passe)
    {

        $data = array();

        $isValid = false;



        $erreurMessage = '';


        // Verification de la validité des données

        if (!User::isExiste($login, $mot_passe)) {
            $erreurMessage = "Le login ou le mot de passe est incorrect";

        } else {

            $erreurMessage = '';

            $isValid = true;
        }

        return $data = [


            'isValid' => $isValid,

            'erreurMessage' => $erreurMessage,


        ];
    }



    /**
     * Verification de l' authenttification '


     * @param  string $login



     * @return  User
     */
    public static function login_User($login)
    {

        $user = User::where('login', '=', $login)
            ->orWhere('email', '=', $login)
            ->first();

        return $user;
    }




    /**
     * Génerer l' administrateur
     *

     * @return  User $user
     */

    public static function genererUser()
    {

        $nom = 'Adanlete';
        $prenom = 'Manivelle';
        $login = 'admin';
        $mot_passe = "admin";


        $annee = User::addUser(

            $nom,
            $prenom,
            $login,
            $mot_passe,
            Role::ADMIN,
            'admin@gmail.com',
            null

        );



        return $annee;
    }
}
