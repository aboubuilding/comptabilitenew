<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssuranceVehicule extends Model
{
    protected $table = 'assurances_vehicules';

    protected $fillable = [
        'voiture_id', 'compagnie_assurance', 'numero_contrat',
        'date_debut', 'date_fin', 'prime', 'type_assurance',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'prime'      => 'float',
    ];

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function isActive(): bool
    {
        return $this->date_fin->isFuture();
    }
}