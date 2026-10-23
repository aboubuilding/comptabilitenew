<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\DepenseStatut;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depense extends Model
{
    protected $table = 'depenses';

    protected $fillable = [
        'libelle', 'beneficiaire', 'motif_depense', 'date_depense', 'montant', 'ligne_budgetaire_id',
        'justificatif_path', 'justificatif_nom_original',
        'annee_id', 'utilisateur_id',
        'statut_depense',
        'valide_par', 'date_validation', 'motif_rejet',
        'caisse_id', 'date_reglement', 'regle_par', 'montant_decaisse', 'etat',
        'etat',
    ];

    protected $casts = [
        'statut_depense'   => DepenseStatut::class,
        'date_depense'     => 'date',
        'date_validation'  => 'datetime',
        'date_reglement'   => 'datetime',
        'montant'          => 'float',
    ];

    /* ===================== Relations ===================== */

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function regleur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regle_par');
    }

    /** Ligne budgétaire à laquelle la dépense est imputée. */
    public function ligneBudgetaire(): BelongsTo
    {
        return $this->belongsTo(Prevision::class, 'ligne_budgetaire_id');
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    /* ===================== Helpers ===================== */

    public function isEnAttente(): bool
    {
        return $this->statut_depense === DepenseStatut::EN_ATTENTE;
    }

    public function isValidee(): bool
    {
        return $this->statut_depense === DepenseStatut::VALIDEE;
    }

    public function isRejetee(): bool
    {
        return $this->statut_depense === DepenseStatut::REJETEE;
    }

    public function isReglee(): bool
    {
        return $this->date_reglement !== null;
    }

    public function peutEtreReglee(): bool
    {
        return $this->isValidee() && ! $this->isReglee();
    }
}