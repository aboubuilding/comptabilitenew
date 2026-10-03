<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deduction extends Model
{
    protected $table = 'deductions';

    protected $fillable = [
        'personnel_id', 'cnss', 'charges_familiale',
        'forfait_professionnel', 'annee_id', 'etat',
    ];

    protected $casts = [
        'cnss'                => 'float',
        'charges_familiale'   => 'float',
        'forfait_professionnel'=> 'float',
    ];

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'personnel_id');
    }

    /** Total des déductions (CNSS + charges familiales + forfait). */
    public function getTotalAttribute(): float
    {
        return round(
            (float) $this->cnss
            + (float) $this->charges_familiale
            + (float) $this->forfait_professionnel,
            2
        );
    }
}