<?php

namespace App\Domain\Logistique\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailAchat extends Model
{
    protected $table = 'detail_achats';

    protected $fillable = [
        'achat_id', 'produit_id', 'annee_id',
        'quantite', 'prix_unitaire', 'montant_achat', 'etat',
    ];

    protected $casts = [
        'quantite'      => 'float',
        'prix_unitaire' => 'float',
        'montant_achat' => 'float',
    ];

    public function achat(): BelongsTo
    {
        return $this->belongsTo(Achat::class, 'achat_id');
    }

    /** Relation tolérante : le modèle Produit arrive avec le module 3.3. */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}