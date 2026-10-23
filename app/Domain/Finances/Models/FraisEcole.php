<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FraisEcole extends Model
{
    protected $table = 'frais_ecoles';

    protected $fillable = [
        'libelle', 'montant', 'type_paiement', 'modalite_paiement', 'type_forfait',
        'niveau_id', 'annee_id', 'plan_echeancier_id', 'etat',
        // Fiche alimentée par ce frais (synchronisation des catalogues)
        'produit_id', 'activite_id', 'evenement_id',
    ];

    protected $casts = [
        'type_paiement'     => TypeDetail::class,   // nature du frais (codes historiques)
        'modalite_paiement' => TypePaiement::class, // comptant / échelonné
        'type_forfait'  => TypeForfait::class,
        'montant'       => 'float',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function planEcheancier(): BelongsTo
    {
        return $this->belongsTo(PlanEcheancier::class, 'plan_echeancier_id');
    }

    public function tranches(): HasMany
    {
        return $this->hasMany(Tranche::class, 'frais_ecole_id');
    }

    public function souscriptions(): HasMany
    {
        return $this->hasMany(Souscription::class, 'frais_ecole_id');
    }
}