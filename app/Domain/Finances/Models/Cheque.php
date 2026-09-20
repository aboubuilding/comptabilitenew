<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\ChequeStatut;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    protected $table = 'cheques';

    protected $fillable = [
        'numero', 'emetteur', 'montant',
        'annee_id', 'paiement_id',
        'banque_id',
        'date_emission', 'date_encaissement', 'date_rapprochement',
        'statut', 'motif_rejet',
        'rapproche_par',
        'etat',
    ];

    protected $casts = [
        'statut'             => ChequeStatut::class,
        'montant'            => 'float',
        'date_emission'      => 'date',
        'date_encaissement'  => 'date',
        'date_rapprochement' => 'date',
    ];

    /* ===================== Relations ===================== */

    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'banque_id');
    }

    /** Utilisateur (modèle User) qui a effectué le rapprochement. */
    public function rapprocheur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rapproche_par');
    }

    /* ===================== Helpers ===================== */

    public function isEnAttente(): bool
    {
        return $this->statut === ChequeStatut::EMIS;
    }
}