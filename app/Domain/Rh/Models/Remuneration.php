<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remuneration extends Model
{
    protected $table = 'remunerations';

    protected $fillable = [
        'employe_id', 'salaire_base', 'prime_anciennete', 'sursalaire',
        'bonus', 'logement', 'nourriture', 'deplacement', 'autre_avantage',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'salaire_base'    => 'float',
        'prime_anciennete'=> 'float',
        'sursalaire'      => 'float',
        'bonus'           => 'float',
        'logement'        => 'float',
        'nourriture'      => 'float',
        'deplacement'     => 'float',
        'autre_avantage'  => 'float',
    ];

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }

    /** Total des primes et avantages (hors salaire de base). */
    public function getTotalPrimesAttribute(): float
    {
        return round(
            (float) $this->prime_anciennete
            + (float) $this->sursalaire
            + (float) $this->bonus
            + (float) $this->logement
            + (float) $this->nourriture
            + (float) $this->deplacement
            + (float) $this->autre_avantage,
            2
        );
    }

    /** Brut total = salaire de base + primes + avantages. */
    public function getBrutTotalAttribute(): float
    {
        return round((float) $this->salaire_base + $this->total_primes, 2);
    }
}