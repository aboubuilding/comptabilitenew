<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chauffeur extends Model
{
    protected $table = 'chauffeurs';

    protected $fillable = [
        'nom', 'prenom', 'permis_conduire', 'date_validite_permis',
        'telephone', 'email', 'adresse', 'statut', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_validite_permis' => 'date',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationVehicule::class, 'chauffeur_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'chauffeur_id');
    }

    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function isActif(): bool
    {
        return (int) $this->statut === 1;
    }

    /** Permis valide (date > aujourd'hui). */
    public function isPermisValide(): bool
    {
        if (! $this->date_validite_permis) return true;
        return $this->date_validite_permis->isFuture();
    }
}