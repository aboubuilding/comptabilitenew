<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEcheancier extends Model
{
    use HasFactory;

    protected $table = 'plan_echeanciers';

    protected $fillable = [
        'nom',
        'description',
        'annee_id',
        'etat',
    ];

    protected $casts = [
        'annee_id' => 'integer',
        'etat' => 'integer',
    ];

    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Relation avec l'année
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    /**
     * Relation avec les lignes d'échéancier
     */
    public function lignes()
    {
        return $this->hasMany(PlanEcheancierLigne::class)->orderBy('ordre');
    }

    /**
     * Relation avec les frais d'école
     */
    public function fraisEcoles()
    {
        return $this->hasMany(FraisEcole::class);
    }

    /**
     * Vérifier si le plan est actif
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Obtenir le nombre de tranches
     */
    public function getNombreTranchesAttribute(): int
    {
        return $this->lignes()->count();
    }

    /**
     * Obtenir le montant total
     */
    public function getMontantTotalAttribute(): float
    {
        return $this->lignes()->sum('montant');
    }

    /**
     * Scope pour les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les plans par année
     */
    public function scopeByAnnee($query, int $anneeId)
    {
        return $query->where('annee_id', $anneeId);
    }
}
