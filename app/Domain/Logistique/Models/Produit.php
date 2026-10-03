<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeProduit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produit extends Model
{
    protected $table = 'produits';

    protected $fillable = [
        'libelle', 'prix_unitaire', 'photo',
        'unite_stock', 'unite_achat', 'equivalence',
        'type_produit', 'etat',
    ];

    protected $casts = [
        'prix_unitaire' => 'float',
        'equivalence'   => 'float',
        'type_produit'  => TypeProduit::class,
    ];

    public function stocksActuels(): HasMany
    {
        return $this->hasMany(StockActuel::class, 'produit_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Stock::class, 'produit_id');
    }

    public function detailBons(): HasMany
    {
        return $this->hasMany(DetailBon::class, 'produit_id');
    }

    public function detailAchats(): HasMany
    {
        return $this->hasMany(DetailAchat::class, 'produit_id');
    }

    /* ================================================================
       Accesseurs calculés à la volée
       ================================================================ */

    /** Quantité totale, tous magasins confondus. */
    public function getStockTotalAttribute(): float
    {
        return round((float) $this->stocksActuels->sum('quantite'), 2);
    }

    /** Valeur totale du stock (tous magasins). */
    public function getValeurStockTotalAttribute(): float
    {
        return round((float) $this->stocksActuels->sum('valeur_stock'), 2);
    }

    /**
     * Seuil d'alerte effectif : celui du stock_actuel s'il existe pour un
     * magasin donné, sinon celui du produit. Simplification : on retourne
     * le maximum des seuils configurés (alerte au plus restrictif).
     */
    public function getSeuilAlerteEffectifAttribute(): float
    {
        $seuils = $this->stocksActuels->pluck('seuil_alerte')->filter()->all();
        return $seuils ? max($seuils) : 0.0;
    }

    public function getEstEnAlerteAttribute(): bool
    {
        $seuil = $this->seuil_alerte_effectif;
        return $seuil > 0 && $this->stock_total <= $seuil;
    }

    public function getEstEnRuptureAttribute(): bool
    {
        return $this->stock_total <= 0;
    }
}