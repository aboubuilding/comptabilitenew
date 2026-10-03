<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\TypeEvenement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evenement extends Model
{
    protected $table = 'evenements';

    protected $fillable = [
        'nom', 'type', 'date_evenement', 'participation',
        'capacite', 'capacite_limitee', 'date_limite_confirmation',
        'description', 'annee_id', 'etat',
    ];

    protected $casts = [
        'type'                   => TypeEvenement::class,
        'date_evenement'         => 'date',
        'date_limite_confirmation'=> 'date',
        'participation'          => 'float',
        'capacite_limitee'       => 'boolean',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(ParticipationEvenement::class, 'evenement_scolaire_id');
    }

    public function isCapaciteLimitee(): bool
    {
        return (bool) $this->capacite_limitee;
    }

    public function isDateLimitePassee(): bool
    {
        return $this->date_limite_confirmation
            && $this->date_limite_confirmation->isPast();
    }

    /** Nombre de participants confirmés. */
    public function getNombreConfirmesAttribute(): int
    {
        return $this->participations()
            ->where('statut', \App\Domain\ServicesEleves\Types\StatutParticipation::CONFIRME->value)
            ->count();
    }

    /** Nombre de pré-inscrits en attente de confirmation. */
    public function getNombrePreInscritsAttribute(): int
    {
        return $this->participations()
            ->where('statut', \App\Domain\ServicesEleves\Types\StatutParticipation::PRE_INSCRIT->value)
            ->count();
    }

    /** Places restantes (null si pas de capacité limitée). */
    public function getPlacesRestantesAttribute(): ?int
    {
        if (! $this->isCapaciteLimitee() || ! $this->capacite) {
            return null;
        }
        return max(0, (int) $this->capacite - $this->nombre_confirmes);
    }

    /** Taux de remplissage (%). */
    public function getTauxRemplissageAttribute(): float
    {
        if (! $this->isCapaciteLimitee() || ! $this->capacite) {
            return 0.0;
        }
        return round(($this->nombre_confirmes / (int) $this->capacite) * 100, 1);
    }
}