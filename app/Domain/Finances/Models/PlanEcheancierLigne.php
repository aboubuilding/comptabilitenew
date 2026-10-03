<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEcheancierLigne extends Model
{
    protected $table = 'plan_echeancier_lignes';

    protected $fillable = [
        'plan_echeancier_id', 'ordre', 'jour_echeance', 'date_echeance',
        'montant', 'pourcentage', 'libelle', 'etat',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'montant'       => 'float',
        'pourcentage'   => 'float',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanEcheancier::class, 'plan_echeancier_id');
    }
}