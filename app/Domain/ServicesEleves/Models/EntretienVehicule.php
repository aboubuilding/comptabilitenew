<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntretienVehicule extends Model
{
    protected $table = 'entretiens_vehicules';

    protected $fillable = [
        'voiture_id', 'date_entretien', 'type_entretien', 'cout',
        'kilometrage', 'observations', 'effectue_par', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_entretien' => 'date',
        'cout'           => 'float',
    ];

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class, 'effectue_par');
    }
}