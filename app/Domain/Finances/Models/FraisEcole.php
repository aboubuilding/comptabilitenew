<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraisEcole extends Model
{
    use HasFactory;

    protected $table = 'frais_ecoles';

    protected $fillable = [
        'libelle', 'montant', 'type_paiement', 'type_forfait',
        'niveau_id', 'annee_id', 'plan_echeancier_id', 'etat',
    ];

    protected $casts = [
        'montant'       => 'float',
        'type_paiement' => TypePaiement::class,
        'type_forfait'  => TypeForfait::class,
        'etat'          => 'integer',
    ];

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function planEcheancier()
    {
        return $this->belongsTo(PlanEcheancier::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}