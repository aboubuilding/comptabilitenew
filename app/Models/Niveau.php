<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    use HasFactory;

    protected $table = 'niveaux';

    protected $fillable = [
        'libelle',
        'description',
        'numero_ordre',
        'cycle_id',
        'etat',
    ];

    protected $casts = [
        'numero_ordre' => 'integer',
        'cycle_id' => 'integer',
        'etat' => 'integer',
    ];

    // Constantes d'état
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Relation avec le cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Vérifier si le niveau est actif
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
     * Obtenir le libellé du cycle
     */
    public function getCycleLibelleAttribute(): string
    {
        return $this->cycle ? $this->cycle->libelle : '-';
    }

    /**
     * Scope pour les niveaux actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les niveaux par cycle
     */
    public function scopeByCycle($query, int $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    /**
     * Scope pour trier par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('numero_ordre', 'asc');
    }
}
