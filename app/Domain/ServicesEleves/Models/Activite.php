<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\TypeActivite;
use App\Domain\Scolarite\Models\Niveau;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activite extends Model
{
    protected $table = 'activites';

    protected $fillable = [
        'libelle', 'description', 'montant',
        'type', 'encadreur', 'contact_encadreur',
        'niveau_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'type'    => TypeActivite::class,
        'montant' => 'float',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionActivite::class, 'activite_id');
    }

    public function getNombreInscritsAttribute(): int
    {
        return $this->inscriptions()
            ->whereIn('statut', [
                \App\Domain\ServicesEleves\Types\StatutInscriptionActivite::EN_ATTENTE->value,
                \App\Domain\ServicesEleves\Types\StatutInscriptionActivite::CONFIRMEE->value,
            ])
            ->count();
    }
}