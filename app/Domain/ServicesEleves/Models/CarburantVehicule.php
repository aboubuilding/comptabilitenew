<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarburantVehicule extends Model
{
    protected $table = 'carburant_vehicules';

    protected $fillable = [
        'voiture_id', 'date_plein', 'quantite_litres', 'prix_unitaire',
        'montant_total', 'kilometrage', 'station_service', 'facture', 'annee_id',
    ];

    protected $casts = [
        'date_plein'      => 'date',
        'quantite_litres' => 'float',
        'prix_unitaire'   => 'float',
        'montant_total'   => 'float',
    ];

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }
}