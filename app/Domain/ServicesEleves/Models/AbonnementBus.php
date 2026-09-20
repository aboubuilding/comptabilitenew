<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonnementBus extends Model
{
    use HasFactory;

    protected $table = 'abonnements_bus';

    protected $fillable = [
        'inscription_id', 'date_debut', 'date_fin', 'montant_mensuel', 'nombre_mois',
        'montant_total_du', 'statut', 'date_abandon', 'motif_abandon', 'abandonne_par',
    ];

    protected $casts = [
        'date_debut'         => 'date',
        'date_fin'           => 'date',
        'montant_mensuel'    => 'float',
        'montant_total_du'   => 'float',
        'statut'             => 'integer', // 1 = actif, 0 = abandonné
        'date_abandon'       => 'date',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function isActif(): bool
    {
        return (int) $this->statut === 1;
    }
}