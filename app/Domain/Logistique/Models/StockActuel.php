<?php

namespace App\Domain\Logistique\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockActuel extends Model
{
    protected $table = 'stock_actuel';

    protected $fillable = [
        'produit_id', 'magasin_id', 'quantite',
        'seuil_alerte', 'valeur_stock', 'date_dernier_mouvement',
    ];

    protected $casts = [
        'quantite'               => 'float',
        'seuil_alerte'           => 'float',
        'valeur_stock'           => 'float',
        'date_dernier_mouvement' => 'date',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function isEnAlerte(): bool
    {
        return $this->seuil_alerte !== null && $this->quantite <= $this->seuil_alerte;
    }

    public function isEnRupture(): bool
    {
        return $this->quantite <= 0;
    }
}