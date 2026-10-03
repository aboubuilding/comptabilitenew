<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\ServicesEleves\Types\StatutInscriptionCantine;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InscriptionCantine extends Model
{
    protected $table = 'inscriptions_cantine';

    protected $fillable = [
        'inscription_id', 'frais_ecole_id',
        'date_debut', 'date_fin',
        'montant_mensuel', 'nombre_mois', 'montant_total_du',
        'statut', 'date_abandon', 'motif_abandon', 'abandonne_par',
        'annee_id',
    ];

    protected $casts = [
        'statut'          => StatutInscriptionCantine::class,
        'date_debut'      => 'date',
        'date_fin'        => 'date',
        'date_abandon'    => 'date',
        'montant_mensuel' => 'float',
        'montant_total_du'=> 'float',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function fraisEcole(): BelongsTo
    {
        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }

    public function abandonnePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abandonne_par');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class, 'inscription_cantine_id');
    }

    public function isActif(): bool
    {
        return $this->statut === StatutInscriptionCantine::ACTIF;
    }
}