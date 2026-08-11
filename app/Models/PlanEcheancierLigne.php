<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEcheancierLigne extends Model
{
    use HasFactory;

    protected $table = 'plan_echeancier_lignes';

    protected $fillable = [
        'plan_echeancier_id',
        'ordre',
        'jour_echeance',
        'date_echeance',
        'montant',
        'pourcentage',
        'libelle',
        'etat',
    ];

    protected $casts = [
        'plan_echeancier_id' => 'integer',
        'ordre' => 'integer',
        'jour_echeance' => 'integer',
        'date_echeance' => 'date',
        'montant' => 'decimal:2',
        'pourcentage' => 'decimal:2',
        'etat' => 'integer',
    ];

    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    /**
     * Relation avec le plan d'échéancier
     */
    public function planEcheancier()
    {
        return $this->belongsTo(PlanEcheancier::class);
    }

    /**
     * Vérifier si la ligne est active
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Scope pour les lignes actives
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les lignes par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }
}
