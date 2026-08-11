<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    protected $table = 'cycles';

    protected $fillable = [
        'libelle',
        'etat',
    ];

    protected $casts = [
        'etat' => 'integer',
    ];

    // Constantes d'état
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Vérifier si le cycle est actif
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Obtenir le libellé de l'état
     */
    public function getEtatLabelAttribute(): string
    {
        return $this->etat === self::ETAT_ACTIF ? 'Actif' : 'Inactif';
    }

    /**
     * Obtenir la classe CSS du statut
     */
    public function getEtatBadgeClassAttribute(): string
    {
        return $this->etat === self::ETAT_ACTIF ? 'badge-success' : 'badge-danger';
    }

    /**
     * Scope pour les cycles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les cycles inactifs
     */
    public function scopeInactive($query)
    {
        return $query->where('etat', self::ETAT_INACTIF);
    }
}
