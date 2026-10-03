<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\Scolarite\Models\Eleve;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscriptionActivite extends Model
{
    protected $table = 'inscriptions_activites';

    protected $fillable = [
        'eleve_id', 'activite_id', 'annee_id',
        'date_inscription', 'montant_du', 'statut', 'etat',
    ];

    protected $casts = [
        'statut'           => StatutInscriptionActivite::class,
        'date_inscription' => 'date',
        'montant_du'       => 'float',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class, 'activite_id');
    }

    public function isActive(): bool
    {
        return in_array($this->statut, [
            StatutInscriptionActivite::EN_ATTENTE,
            StatutInscriptionActivite::CONFIRMEE,
        ], true);
    }
}