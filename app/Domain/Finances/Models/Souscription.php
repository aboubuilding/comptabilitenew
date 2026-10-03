<?php

namespace App\Domain\Finances\Models;

use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Souscription extends Model
{
    protected $table = 'souscriptions';

    protected $fillable = [
        'date_souscription', 'montant_annuel_prevu', 'taux_remise',
        'type_paiement', 'frais_ecole_id', 'niveau_id', 'annee_id',
        'inscription_id', 'utilisateur_id', 'ligne_id', 'zone_id', 'etat',
    ];

    protected $casts = [
        'date_souscription'    => 'date',
        'montant_annuel_prevu' => 'float',
        'taux_remise'          => 'float',
    ];

    public function fraisEcole(): BelongsTo
    {
        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /** Montant après application de la remise. */
    public function getMontantNetAttribute(): float
    {
        return round(
            (float) $this->montant_annuel_prevu * (1 - (float) $this->taux_remise / 100),
            2
        );
    }

    /** Montant total de la remise accordée. */
    public function getMontantRemiseAttribute(): float
    {
        return round(
            (float) $this->montant_annuel_prevu * ((float) $this->taux_remise / 100),
            2
        );
    }
}