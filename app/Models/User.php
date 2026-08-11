<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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

    protected $hidden = [
        'mot_passe',
        'remember_token',
    ];

    protected $casts = [
        'etat' => 'integer',
        'role' => 'integer',
    ];

    // Constantes de rôle
    const ROLE_ADMIN = 1;
    const ROLE_DIRECTEUR = 2;
    const ROLE_COMPTABLE = 3;
    const ROLE_ADMIN_ADJOINT = 4;
    const ROLE_CAISSIER = 5;
    const ROLE_SECRETAIRE = 6;
    const ROLE_ENSEIGNANT = 7;
    const ROLE_PARENT = 8;

    // Constantes d'état
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Obtenir le mot de passe pour l'authentification.
     * Laravel attend 'password' par défaut, on redirige vers 'mot_passe'
     */
    public function getAuthPassword()
    {
        return $this->mot_passe;
    }

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Vérifier si l'utilisateur est administrateur
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Obtenir le nom complet de l'utilisateur
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->prenom ?? '') . ' ' . ($this->nom ?? ''));
    }

    /**
     * Obtenir le libellé du rôle
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_DIRECTEUR => 'Directeur',
            self::ROLE_COMPTABLE => 'Comptable',
            self::ROLE_ADMIN_ADJOINT => 'Admin Adjoint',
            self::ROLE_CAISSIER => 'Caissier',
            self::ROLE_SECRETAIRE => 'Secrétaire',
            self::ROLE_ENSEIGNANT => 'Enseignant',
            self::ROLE_PARENT => 'Parent',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la classe CSS du rôle
     */
    public function getRoleBadgeClassAttribute(): string
    {
        return match($this->role) {
            self::ROLE_ADMIN => 'badge-danger',
            self::ROLE_DIRECTEUR => 'badge-primary',
            self::ROLE_COMPTABLE => 'badge-success',
            self::ROLE_ADMIN_ADJOINT => 'badge-info',
            self::ROLE_CAISSIER => 'badge-warning',
            self::ROLE_SECRETAIRE => 'badge-secondary',
            self::ROLE_ENSEIGNANT => 'badge-dark',
            self::ROLE_PARENT => 'badge-info',
            default => 'badge-secondary',
        };
    }

    /**
     * Obtenir le libellé de l'état
     */
    public function getEtatLabelAttribute(): string
    {
        return $this->etat === self::ETAT_ACTIF ? 'Actif' : 'Inactif';
    }

    /**
     * Scope pour les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les administrateurs
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope par rôle
     */
    public function scopeByRole($query, int $role)
    {
        return $query->where('role', $role);
    }
}
