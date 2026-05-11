<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'login',
        'email',
        'mot_passe',
        'password',
        'photo',
        'role',
        'etat',
        'email_verified_at',
        'remember_token',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Les attributs qui doivent être cachés pour les tableaux.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'mot_passe',
        'password',
        'remember_token',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'etat' => 'integer',
        'role' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────
    // Accesseurs & Mutateurs
    // ─────────────────────────────────────────────────────────────

    /**
     * Récupère le mot de passe pour l'authentification (priorité à password).
     */
    public function getAuthPassword()
    {
        return $this->password ?? $this->mot_passe;
    }

    // ─────────────────────────────────────────────────────────────
    // Relations (optionnelles, à ajouter selon vos besoins)
    // ─────────────────────────────────────────────────────────────

    // Exemple : un utilisateur peut créer des paiements
    // public function paiements()
    // {
    //     return $this->hasMany(Paiement::class, 'utilisateur_id');
    // }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'utilisateur est actif.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique.
     */
    public function hasRole(int $role): bool
    {
        return $this->role == $role;
    }

    /**
     * Raccourci pour définir le nom complet.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Raccourci pour l'affichage du rôle.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            1 => 'Admin',
            2 => 'Comptable',
            3 => 'Directeur',
            4 => 'Caissier',
            5 => 'Parent',
            default => 'Inconnu',
        };
    }
}
