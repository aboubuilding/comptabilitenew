<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne = une échéance du plan (ce que le cahier des charges appelle
 * "une tranche de paiement"). Montant fixe OU pourcentage — au moins un
 * des deux selon l'usage prévu par l'écran qui la crée.
 */
class PlanEcheancierLigne extends Model
{
    use HasFactory;

    protected $table = 'plan_echeancier_lignes';

    protected $fillable = [
        'plan_echeancier_id', 'ordre', 'jour_echeance', 'date_echeance',
        'montant', 'pourcentage', 'libelle', 'etat',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'montant'       => 'float',
        'pourcentage'   => 'float',
        'etat'          => 'integer',
    ];

    public function planEcheancier()
    {
        return $this->belongsTo(PlanEcheancier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}