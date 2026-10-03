<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanEcheancier extends Model
{
    protected $table = 'plan_echeanciers';

    protected $fillable = ['nom', 'description', 'annee_id', 'etat'];

    public function lignes(): HasMany
    {
        // Une modification du plan remplace ses lignes : les anciennes sont
        // désactivées, seules les lignes actives font partie du plan.
        return $this->hasMany(PlanEcheancierLigne::class, 'plan_echeancier_id')
            ->where('etat', 1)
            ->orderBy('ordre');
    }

    public function fraisEcoles(): HasMany
    {
        return $this->hasMany(FraisEcole::class, 'plan_echeancier_id');
    }

    /** Somme des pourcentages (doit faire 100) ou des montants. */
    public function getTotalPourcentageAttribute(): float
    {
        return round((float) $this->lignes->sum('pourcentage'), 2);
    }

    public function getTotalMontantAttribute(): float
    {
        return round((float) $this->lignes->sum('montant'), 2);
    }
}