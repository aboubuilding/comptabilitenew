<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\TypeDepenseVehicule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepenseVoiture extends Model
{
    protected $table = 'depense_voitures';

    protected $fillable = [
        'libelle', 'date_depense', 'montant',
        'voiture_id', 'zone_id', 'type_depense', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_depense' => 'date',
        'montant'      => 'float',
        'type_depense' => TypeDepenseVehicule::class,
    ];

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}