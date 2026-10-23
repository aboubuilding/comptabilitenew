<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DossierRecouvrement extends Model
{
    protected $table = 'dossiers_recouvrement';

    protected $fillable = [
        'inscription_id', 'nature',
        'montant_initial_du', 'montant_recouvre', 'statut',
        'date_ouverture', 'date_cloture', 'motif_cloture', 'paiement_cloture_id',
        'utilisateur_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'nature'             => NatureCreance::class,
        'statut'             => DossierStatut::class,
        'montant_initial_du' => 'float',
        'montant_recouvre'   => 'float',
        'date_ouverture'     => 'date',
        'date_cloture'       => 'date',
    ];

    /** Inscription concernée : donne l'élève, sa classe et sa famille. */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Scolarite\Models\Inscription::class, 'inscription_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /** Échéances réclamées (reprises à chaque recalcul). */
    public function echeances(): HasMany
    {
        return $this->hasMany(DossierEcheance::class, 'dossier_recouvrement_id')->orderBy('date_echeance');
    }

    /** Paiement qui a soldé le dossier. */
    public function paiementCloture(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_cloture_id');
    }

    public function relances(): HasMany
    {
        return $this->hasMany(Relance::class, 'dossier_recouvrement_id')
            ->orderByDesc('date_relance');
    }

    /** Solde restant à recouvrer. */
    public function getMontantRestantAttribute(): float
    {
        return round(
            (float) $this->montant_initial_du - (float) $this->montant_recouvre,
            2
        );
    }

    public function isClos(): bool
    {
        return $this->statut?->isClos() ?? false;
    }

    /** Dernière relance en date — utile pour l'affichage index. */
    public function derniereRelance(): ?Relance
    {
        return $this->relances->first();
    }
}