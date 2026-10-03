<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Logistique\Models\Produit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuProduit extends Model
{
    protected $table = 'menu_produits';

    protected $fillable = [
        'menu_id', 'produit_id', 'quantite', 'cout_unitaire', 'cout_total',
    ];

    protected $casts = [
        'quantite'     => 'float',
        'cout_unitaire'=> 'float',
        'cout_total'   => 'float',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /** Calcule automatiquement le coût total. */
    public function recalculerCout(): void
    {
        if ($this->cout_unitaire !== null) {
            $this->cout_total = round($this->quantite * $this->cout_unitaire, 2);
            $this->save();
        }
    }
}