<?php

namespace App\Domain\Rh\Models;

use App\Domain\Rh\Types\StatutPaie;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paie extends Model
{
    protected $table = 'paies';

    protected $fillable = [
        'personnel_id', 'periode_id',
        'salaire_base', 'prime_anciennete', 'sursalaire', 'bonus',
        'logement', 'nourriture', 'deplacement', 'autre_avantage',
        'total_brut', 'total_deductions', 'total_retenues', 'net_a_payer',
        'avantage_concede', 'prelevement_mensuel',
        'statut', 'date_paiement', 'paye_par', 'observations', 'reference',

        'mode_paiement', 'banque', 'iban',
    'retire_a_la_billetterie', 'date_retrait', 'retire_par',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'statut'          => StatutPaie::class,
        'date_paiement'   => 'date',
        'salaire_base'    => 'float',
        'prime_anciennete'=> 'float',
        'sursalaire'      => 'float',
        'bonus'           => 'float',
        'logement'        => 'float',
        'nourriture'      => 'float',
        'deplacement'     => 'float',
        'autre_avantage'  => 'float',
        'total_brut'      => 'float',
        'total_deductions'=> 'float',
        'total_retenues'  => 'float',
        'net_a_payer'     => 'float',
        'avantage_concede'=> 'float',
        'prelevement_mensuel'=> 'float',
        'retire_a_la_billetterie' => 'boolean',
    'date_retrait'            => 'date',
    
    
    ];

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'personnel_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class, 'periode_id');
    }

    public function payeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paye_par');
    }

    public function isPaye(): bool
    {
        return $this->statut === StatutPaie::PAYE;
    }

    public function isBrouillon(): bool
    {
        return $this->statut === StatutPaie::BROUILLON;
    }


    public function retireur(): BelongsTo
{
    return $this->belongsTo(User::class, 'retire_par');
}

public function isPayeParBanque(): bool
{
    return $this->mode_paiement === 'banque';
}

public function isPayeABilletterie(): bool
{
    return $this->mode_paiement === 'billette';
}

/** Le paiement est fait mais l'employé n'est pas encore venu chercher ses espèces. */
public function isEnAttenteRetrait(): bool
{
    return $this->isPaye()
        && $this->isPayeABilletterie()
        && ! $this->retire_a_la_billetterie;
}

/** Nombre de jours écoulés depuis le paiement (pour mesurer le retard de retrait). */
public function getJoursDepuisPaiementAttribute(): int
{
    if (! $this->date_paiement) return 0;
    return (int) $this->date_paiement->diffInDays(now());
}
}