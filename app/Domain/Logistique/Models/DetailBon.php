<?php

namespace App\Domain\Logistique\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailBon extends Model
{
    protected $table = 'detail_bons';

    protected $fillable = [
        'bon_id', 'produit_id', 'libelle',
        'quantite', 'prix_unitaire', 'montant',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'quantite'      => 'float',
        'prix_unitaire' => 'float',
        'montant'       => 'float',
    ];

    public function bon(): BelongsTo
    {
        return $this->belongsTo(Bon::class, 'bon_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}