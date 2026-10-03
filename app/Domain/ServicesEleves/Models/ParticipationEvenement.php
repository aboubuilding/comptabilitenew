<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\StatutParticipation;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipationEvenement extends Model
{
    protected $table = 'participation_evenements';

    protected $fillable = [
        'evenement_scolaire_id', 'inscription_id', 'inscripion_id',
        'date_inscription', 'montant_facture', 'facture_id',
        'statut', 'utilisateur_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'statut'          => StatutParticipation::class,
        'date_inscription'=> 'date',
        'montant_facture' => 'float',
    ];

    public function evenement(): BelongsTo
    {
        return $this->belongsTo(Evenement::class, 'evenement_scolaire_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function isConfirme(): bool
    {
        return $this->statut === StatutParticipation::CONFIRME;
    }

    public function isPreInscrit(): bool
    {
        return $this->statut === StatutParticipation::PRE_INSCRIT;
    }
}