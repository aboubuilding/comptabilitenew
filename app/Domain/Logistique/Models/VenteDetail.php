<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenteDetail extends Model
{
    protected $table = 'vente_details';

    // montant_ht et montant_ttc sont des colonnes générées (storedAs)
    // donc NON fillable.
    protected $fillable = [
        'vente_id', 'produit_id', 'quantite',
        'prix_unitaire', 'remise', 'inscription_id',
    ];

    protected $casts = [
        'quantite'      => 'float',
        'prix_unitaire' => 'float',
        'remise'        => 'float',
        'montant_ht'    => 'float',
        'montant_ttc'   => 'float',
    ];

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class, 'vente_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }
}