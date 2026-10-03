<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\SensAnalytique;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ImputationAnalytique extends Model
{
    protected $table = 'imputations_analytiques';

    protected $fillable = [
        'imputable_type', 'imputable_id',
        'centre_analytique_id', 'annee_id',
        'montant', 'pourcentage', 'sens',
        'cree_par', 'etat',
    ];

    protected $casts = [
        'sens'        => SensAnalytique::class,
        'montant'     => 'float',
        'pourcentage' => 'float',
    ];

    public function imputable(): MorphTo
    {
        return $this->morphTo();
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CentreAnalytique::class, 'centre_analytique_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    /** Résout le montant effectif : soit direct, soit calculé depuis %. */
    public function montantEffectif(float $montantSource): float
    {
        if ($this->montant !== null) {
            return round($this->montant, 2);
        }

        if ($this->pourcentage !== null) {
            return round($montantSource * ($this->pourcentage / 100), 2);
        }

        return 0.0;
    }
}