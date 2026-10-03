<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Domain\Scolarite\Types\TypeInscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inscription annuelle d'un élève.
 */
class Inscription extends Model
{
    protected $table = 'inscriptions';

    protected $fillable = [
        'date_inscription', 'eleve_id', 'cycle_id', 'niveau_id', 'last_niveau_id',
        'classe_id', 'espace_id', 'type_inscription', 'statut_validation',
        'annee_id', 'parent_id', 'taux_remise',
        'motif_rejet', 'date_validation', 'utilisateur_id',
        'statut_abandon', 'date_abandon', 'motif_abandon', 'abandonne_par',
        'frais_scolarite', 'frais_assurance', 'frais_inscription',
        'frais_cantine', 'frais_bus', 'frais_livre',
        'remise_scolarite', 'frais_examen',
        'is_cantine', 'is_bus', 'is_livre',
        'etat',
    ];

    protected $casts = [
        'date_inscription'  => 'date',
        'date_validation'   => 'datetime',
        'type_inscription'  => TypeInscription::class,
        'statut_validation' => 'integer',
        'annee_id'          => 'integer',
        'eleve_id'          => 'integer',
        'classe_id'         => 'integer',
        'niveau_id'         => 'integer',
        'cycle_id'          => 'integer',
        'etat'              => 'integer',
        'frais_scolarite'   => 'float',
        'frais_assurance'   => 'float',
        'frais_inscription' => 'float',
        'frais_cantine'     => 'float',
        'frais_bus'         => 'float',
        'frais_livre'       => 'float',
        'remise_scolarite'  => 'float',
        'frais_examen'      => 'float',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    /* ===================== Cycle de vie ===================== */

    public function statut(): ?StatutValidationInscription
    {
        return StatutValidationInscription::tryFrom((int) $this->statut_validation);
    }

    public function isEnAttente(): bool
    {
        return ! $this->isAbandonnee()
            && in_array($this->statut(), [StatutValidationInscription::EN_ATTENTE, StatutValidationInscription::EN_COURS], true);
    }

    public function isValidee(): bool
    {
        return $this->statut() === StatutValidationInscription::VALIDEE;
    }

    public function isRejetee(): bool
    {
        return $this->statut() === StatutValidationInscription::REJETEE;
    }

    /** Un abandon en cours d'année est porté par statut_abandon, indépendamment de la validation. */
    public function isAbandonnee(): bool
    {
        return (int) $this->statut_abandon === 1 || $this->statut() === StatutValidationInscription::ABANDONNEE;
    }

    /**
     * Statut à afficher dans les listes : l'abandon prime sur la validation.
     *
     * @return array{label:string, classe:string}
     */
    public function statutAffiche(): array
    {
        if ($this->isAbandonnee()) {
            return ['label' => 'Abandonnée', 'classe' => 'badge-statut-abandon'];
        }

        return match ($this->statut()) {
            StatutValidationInscription::VALIDEE => ['label' => 'Validée', 'classe' => 'badge-statut-validee'],
            StatutValidationInscription::REJETEE => ['label' => 'Rejetée', 'classe' => 'badge-statut-rejetee'],
            StatutValidationInscription::EN_COURS => ['label' => 'En cours', 'classe' => 'badge-statut-attente'],
            default => ['label' => 'En attente', 'classe' => 'badge-statut-attente'],
        };
    }
}