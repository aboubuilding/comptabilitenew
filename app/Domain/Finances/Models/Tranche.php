<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tranche extends Model
{
    protected $table = 'tranches';

    protected $fillable = [
        'libelle', 'date_butoire', 'frais_ecole_id', 'type_frais', 'taux', 'etat',
        // Échéance créée lors de la conversion vers les plans d'échéancier
        'plan_echeancier_ligne_id',
    ];

    protected $casts = [
        'date_butoire' => 'date',
        'taux'         => 'float',
    ];

    public function fraisEcole(): BelongsTo
    {
        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }
}