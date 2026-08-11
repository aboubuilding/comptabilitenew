<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annee extends Model
{
    use HasFactory;

    protected $table = 'annees';

    protected $fillable = [
        'libelle',
        'date_rentree',
        'date_fin',
        'date_ouverture_inscription',
        'date_fermeture_reinscription',
        'statut_annee',
        'etat',
    ];

    protected $casts = [
        'date_rentree' => 'date',
        'date_fin' => 'date',
        'date_ouverture_inscription' => 'date',
        'date_fermeture_reinscription' => 'date',
        'statut_annee' => 'integer',
        'etat' => 'integer',
    ];

    // Constantes de statut
    const STATUT_NON_OUVERT = 1;
    const STATUT_OUVERT = 2;
    const STATUT_CLOTURE = 3;

    // Constantes d'état
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Vérifier si l'année est ouverte
     */
    public function isOpen(): bool
    {
        return $this->statut_annee === self::STATUT_OUVERT;
    }

    /**
     * Vérifier si l'année est clôturée
     */
    public function isClosed(): bool
    {
        return $this->statut_annee === self::STATUT_CLOTURE;
    }

    /**
     * Vérifier si l'année est active
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLabelAttribute(): string
    {
        return match($this->statut_annee) {
            self::STATUT_NON_OUVERT => 'Non ouvert',
            self::STATUT_OUVERT => 'Ouvert',
            self::STATUT_CLOTURE => 'Clôturé',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la classe CSS du statut
     */
    public function getStatutBadgeClassAttribute(): string
    {
        return match($this->statut_annee) {
            self::STATUT_NON_OUVERT => 'badge-warning',
            self::STATUT_OUVERT => 'badge-success',
            self::STATUT_CLOTURE => 'badge-danger',
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
     * Scope pour les années actives
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les années ouvertes
     */
    public function scopeOpen($query)
    {
        return $query->where('statut_annee', self::STATUT_OUVERT);
    }

    /**
     * Scope pour les années clôturées
     */
    public function scopeClosed($query)
    {
        return $query->where('statut_annee', self::STATUT_CLOTURE);
    }

    /**
     * Scope pour les années non ouvertes
     */
    public function scopeNotOpen($query)
    {
        return $query->where('statut_annee', self::STATUT_NON_OUVERT);
    }
}
