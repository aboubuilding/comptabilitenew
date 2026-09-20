<?php

namespace App\Domain\Scolarite\Models;

use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Domain\Scolarite\Types\TypeInscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Réécrit en entier par rapport à la version minimale utilisée pour
 * Élèves : la migration complète porte motif_rejet, date_validation,
 * utilisateur_id (qui a validé) et taux_remise (pourcentage, distinct de
 * remise_scolarite qui est un montant fixe).
 */
class Inscription extends Model
{
    use HasFactory;

    protected $table = 'inscriptions';

    protected $fillable = [
        'date_inscription', 'eleve_id', 'cycle_id', 'niveau_id', 'last_niveau_id',
        'classe_id', 'espace_id', 'type_inscription', 'statut_validation', 'annee_id',
        'parent_id', 'taux_remise',
        'motif_rejet', 'date_validation', 'utilisateur_id',
        'specialite_id_1', 'specialite_id_2', 'specialite_id_3', 'specialite_abandonne',
        'bulletin_1', 'bulletin_2', 'bulletin_3', 'dnb',
        'programme_provenance', 'is_cantine', 'is_bus', 'is_livre',
        'frais_scolarite', 'frais_assurance', 'frais_inscription', 'frais_cantine',
        'frais_bus', 'frais_livre', 'remise_scolarite', 'frais_examen',
        'date_abandon', 'motif_abandon', 'statut_abandon', 'etat',
    ];

    protected $casts = [
        'date_inscription'  => 'date',
        'date_validation'   => 'datetime',
        'date_abandon'      => 'date',
        'statut_abandon'    => 'integer', // 0 = actif, 1 = abandonné
        'statut_validation' => StatutValidationInscription::class,
        'type_inscription'  => TypeInscription::class,
        'taux_remise'       => 'integer',
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

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function isAbandonnee(): bool
    {
        return (int) $this->statut_abandon === 1;
    }

    public function isValidee(): bool
    {
        return $this->statut_validation === StatutValidationInscription::Validee;
    }

    public function isEnAttente(): bool
    {
        return $this->statut_validation === StatutValidationInscription::EnAttente;
    }

    public function isRejetee(): bool
    {
        return $this->statut_validation === StatutValidationInscription::Rejetee;
    }

    /**
     * Statut affiché à l'écran : combine statut_validation ET
     * statut_abandon (deux colonnes distinctes en base) en un badge
     * unique, tel que demandé par le cahier des charges ("en attente,
     * validée, rejetée, abandonnée").
     */
    public function statutAffiche(): array
    {
        if ($this->isAbandonnee()) {
            return ['label' => 'Abandonnée', 'classe' => 'badge-statut-abandon'];
        }

        return match ($this->statut_validation) {
            StatutValidationInscription::Validee   => ['label' => 'Validée', 'classe' => 'badge-statut-validee'],
            StatutValidationInscription::Rejetee   => ['label' => 'Rejetée', 'classe' => 'badge-statut-rejetee'],
            StatutValidationInscription::EnAttente => ['label' => 'En attente', 'classe' => 'badge-statut-attente'],
            default                                 => ['label' => 'Inconnu', 'classe' => 'badge-statut-attente'],
        };
    }

    /**
     * Somme des frais d'engagement, moins la remise scolarité — calcul
     * PROVISOIRE du "montant dû" (voir la même note sur EleveRepository),
     * en attendant la table situations_recouvrement du futur module
     * Recouvrement.
     */
    public function getMontantEngageAttribute(): float
    {
        return (float) ($this->frais_scolarite ?? 0)
            + (float) ($this->frais_cantine ?? 0)
            + (float) ($this->frais_bus ?? 0)
            + (float) ($this->frais_livre ?? 0)
            + (float) ($this->frais_assurance ?? 0)
            + (float) ($this->frais_inscription ?? 0)
            + (float) ($this->frais_examen ?? 0)
            - (float) ($this->remise_scolarite ?? 0);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}