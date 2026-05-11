<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'eleves';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
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
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_naissance' => 'date',
        'sexe' => 'integer',
        'nationalite_id' => 'integer',
        'espace_id' => 'integer',
        'groupe_id' => 'integer',
        'lien_parente_personne' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations (optionnelles, ajustez selon votre schéma)
    // ─────────────────────────────────────────────────────────────

    /**
     * Un élève peut avoir une inscription (pour l'année en cours).
     */
    public function inscription()
    {
        return $this->hasOne(Inscription::class);
    }

    /**
     * Un élève peut avoir plusieurs inscriptions (historique).
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'élève est actif.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Retourne le nom complet de l'élève.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Retourne le libellé du sexe.
     */
    public function getSexeLabelAttribute(): string
    {
        return match ($this->sexe) {
            2=> 'Féminin',
            1 => 'Masculin',
            default => 'Non défini',
        };
    }
}
