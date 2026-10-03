<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\SensTrajet;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonnementBus extends Model
{
    protected $table = 'abonnements_bus';

    protected $fillable = [
        'inscription_id', 'zone_id', 'voiture_id', 'ligne_id',
        'sens', 'point_arret',
        'date_debut', 'date_fin',
        'montant_mensuel', 'nombre_mois', 'montant_total_du',
        'statut', 'date_abandon', 'motif_abandon', 'abandonne_par',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'sens'             => SensTrajet::class,
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'date_abandon'     => 'date',
        'montant_mensuel'  => 'float',
        'montant_total_du' => 'float',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function voiture(): BelongsTo
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class, 'ligne_id');
    }

    public function abandonnePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abandonne_par');
    }

    public function isActif(): bool
    {
        return (int) $this->statut === 1;
    }
}