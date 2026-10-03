<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paiement extends Model
{
    protected $table = 'paiements';

    protected $fillable = [
        'reference', 'payeur', 'telephone_payeur',
        'motif_suppression',
        'date_paiement', 'statut_paiement', 'mode_paiement',
        'inscription_id', 'utilisateur_id', 'cheque_id', 'annee_id',
        'montant', 'montant_recu', 'etat',
    ];

    protected $casts = [
        'statut_paiement' => PaiementStatut::class,
        'mode_paiement'   => ModePaiement::class,
        'date_paiement'   => 'date',
        'montant'         => 'float',
        'montant_recu'    => 'float',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(Detail::class, 'paiement_id');
    }

    /** Inscription réglée : donne l'élève et sa classe. */
    public function inscription(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Scolarite\Models\Inscription::class, 'inscription_id');
    }

    public function comptable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function cheque(): BelongsTo
    {
        return $this->belongsTo(Cheque::class, 'cheque_id');
    }

    public function isEnAttente(): bool
    {
        return $this->statut_paiement === PaiementStatut::EN_ATTENTE;
    }

    public function peutEtreModifie(): bool
    {
        return $this->isEnAttente();
    }
}