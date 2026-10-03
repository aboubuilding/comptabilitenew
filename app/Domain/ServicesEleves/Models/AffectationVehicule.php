<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\TypeAffectation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationVehicule extends Model
{
    protected $table = 'affectations_vehicules';

    protected $fillable = [
        'voiture_id', 'chauffeur_id', 'date_debut', 'date_fin',
        'motif', 'type_affectation', 'annee_id',
    ];

    protected $casts = [
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'type_affectation' => TypeAffectation::class,
    ];

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function chauffeur(): BelongsTo
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_id');
    }

    public function isActive(): bool
    {
        return ! $this->date_fin || $this->date_fin->isFuture();
    }
}