<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chauffeur extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'chauffeurs';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'permis_conduire',
        'date_validite_permis',
        'telephone',
        'email',
        'adresse',
        'statut',
        'annee_id',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_validite_permis' => 'date',
        'statut' => 'integer',
        'annee_id' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Un chauffeur peut être rattaché à une année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si le chauffeur est actif (non supprimé logiquement).
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Vérifie si le chauffeur est actif pour le travail (statut = 1).
     */
    public function isActif(): bool
    {
        return $this->statut == 1;
    }

    /**
     * Raccourci pour le nom complet.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Vérifie si le permis est encore valide.
     */
    public function isPermisValide(): bool
    {
        if (!$this->date_validite_permis) {
            return false;
        }
        return now()->lessThanOrEqualTo($this->date_validite_permis);
    }
}
