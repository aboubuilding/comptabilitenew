<?php

namespace App\Domain\Administration\Models;

use App\Domain\Administration\Types\RoleUtilisateur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Modèle utilisateur standard Laravel (table users).
 * Le mot de passe est porté par la colonne mot_passe.
 */
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
        'role_id',
        'etat',
    ];

    protected $hidden = [
        'mot_passe',
        'remember_token',
    ];

    protected $casts = [
        'etat'          => 'integer',
        'role'          => RoleUtilisateur::class,
        'last_login_at' => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Laravel attend getAuthPassword() pour l'authentification.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->mot_passe;
    }

    /* ===================== Authentification & profil ===================== */

    public function isActive(): bool
    {
        return (int) $this->etat === 1;
    }

    public function isAdmin(): bool
    {
        return $this->aLeProfil(RoleUtilisateur::Administrateur);
    }

    /**
     * Profils complémentaires (cahier des charges § 3 : un utilisateur peut
     * cumuler plusieurs profils). roles.id reprend le code du profil.
     */
    public function profilsComplementaires(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    /**
     * Profil principal suivi des profils complémentaires, sans doublon.
     *
     * @return RoleUtilisateur[]
     */
    public function profils(): array
    {
        $profils = $this->role ? [$this->role] : [];

        foreach ($this->profilsComplementaires as $complementaire) {
            $profil = RoleUtilisateur::tryFrom((int) $complementaire->id);

            if ($profil && ! in_array($profil, $profils, true)) {
                $profils[] = $profil;
            }
        }

        return $profils;
    }

    public function aLeProfil(RoleUtilisateur $profil): bool
    {
        return in_array($profil, $this->profils(), true);
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

    /**
     * Profil applicatif (nouveau référentiel).
     */
    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Nom complet.
     */
    public function getNomCompletAttribute(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }

    /**
     * Scope actifs.
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}