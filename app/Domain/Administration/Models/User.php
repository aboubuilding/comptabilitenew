<?php

namespace App\Domain\Administration\Models;

use App\Domain\Administration\Types\RoleUtilisateur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'nom',
        'prenom',
        'login',
        'email',
        'mot_passe',
        'photo',
        'role',
        'etat',
    ];

    /**
     * last_login_at / last_login_ip sont volontairement absents de
     * $fillable : ils ne sont renseignés que par
     * UserRepository::recordLogin() après une connexion réussie, jamais
     * via un formulaire.
     *
     * Colonne 'password' (ajoutée par improve_users_table) non utilisée :
     * getAuthPassword() renvoie 'mot_passe', qui reste la seule colonne
     * de mot de passe active. À nettoyer si une migration vers le champ
     * natif 'password' est prévue un jour.
     */
    protected $hidden = [
        'mot_passe',
        'password',
        'remember_token',
    ];

    protected $casts = [
        'etat'          => 'integer',
        'role'          => RoleUtilisateur::class,
        'last_login_at' => 'datetime',
    ];

    /**
     * Alignées sur App\Repositories\BaseRepository (ACTIF = 1 / INACTIF =
     * 0, pas de 3e valeur).
     */
    public const ETAT_ACTIF   = 1;
    public const ETAT_INACTIF = 0;

    public function getAuthPassword()
    {
        return $this->mot_passe;
    }

    public function isActive(): bool
    {
        return (int) $this->etat === self::ETAT_ACTIF;
    }

    public function isAdmin(): bool
    {
        return $this->role === RoleUtilisateur::Administrateur;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    public function getRoleLabelAttribute(): ?string
    {
        return $this->role?->label();
    }

    public function getRoleBadgeClassAttribute(): ?string
    {
        return $this->role?->badgeClass();
    }

    public function getEtatLabelAttribute(): string
    {
        return $this->isActive() ? 'Actif' : 'Inactif';
    }

    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', RoleUtilisateur::Administrateur->value);
    }

    public function scopeByRole($query, RoleUtilisateur $role)
    {
        return $query->where('role', $role->value);
    }
}