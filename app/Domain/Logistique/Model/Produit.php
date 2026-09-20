<?php

namespace App\Domain\Logistique\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle minimal — dépendance directe de l'écran Paiements (recherche
 * produit pour le panier). Le module complet Logistique & Achats/Stock
 * reste à construire séparément.
 */
class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'libelle', 'prix_unitaire', 'photo', 'unite_stock', 'unite_achat', 'equivalence',
        'type_produit', 'quantite_stock', 'seuil_alerte', 'stock_min', 'stock_max', 'etat',
    ];

    protected $casts = [
        'prix_unitaire'  => 'float',
        'quantite_stock' => 'integer',
        'etat'           => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }

    public function scopeEnStock($query)
    {
        return $query->where('quantite_stock', '>', 0);
    }
}