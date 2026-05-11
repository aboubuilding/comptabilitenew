<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'produits';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'libelle',
        'description',
        'categorie',
        'photo',
        'type_produit',
        'unite_base',
        'unite_stock',
        'unite_achat',
        'conversion_achat',
        'unite_vente',
        'conversion_vente',
        'prix_achat',
        'prix_vente',
        'quantite_stock',
        'seuil_alerte',
        'stock_min',
        'stock_max',
        'equivalence',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prix_achat' => 'decimal:2',
        'prix_vente' => 'decimal:2',
        'conversion_achat' => 'float',
        'conversion_vente' => 'float',
        'quantite_stock' => 'float',
        'seuil_alerte' => 'float',
        'stock_min' => 'float',
        'stock_max' => 'float',
        'equivalence' => 'float',
        'type_produit' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Méthodes de conversion d'unités
    // ─────────────────────────────────────────────────────────────

    /**
     * Convertit une quantité (dans une unité donnée) vers l'unité de base.
     *
     * @param float $quantite
     * @param string $unite (base, achat, vente)
     * @return float
     */
    public function convertirVersBase(float $quantite, string $unite): float
    {
        $unite = strtolower($unite);
        $base = strtolower($this->unite_base ?? 'piece');

        if ($unite === $base) {
            return $quantite;
        }
        if ($unite === strtolower($this->unite_achat) && $this->conversion_achat) {
            return $quantite * $this->conversion_achat;
        }
        if ($unite === strtolower($this->unite_vente) && $this->conversion_vente) {
            return $quantite * $this->conversion_vente;
        }
        throw new \InvalidArgumentException("Unité '$unite' non reconnue pour le produit {$this->libelle}");
    }

    /**
     * Convertit une quantité depuis l'unité de base vers une unité cible.
     */
    public function convertirDepuisBase(float $quantiteBase, string $uniteCible): float
    {
        $uniteCible = strtolower($uniteCible);
        $base = strtolower($this->unite_base ?? 'piece');

        if ($uniteCible === $base) {
            return $quantiteBase;
        }
        if ($uniteCible === strtolower($this->unite_achat) && $this->conversion_achat) {
            return $quantiteBase / $this->conversion_achat;
        }
        if ($uniteCible === strtolower($this->unite_vente) && $this->conversion_vente) {
            return $quantiteBase / $this->conversion_vente;
        }
        return $quantiteBase; // fallback
    }

    /**
     * Vérifie la disponibilité du stock dans une unité donnée.
     */
    public function checkStock(float $quantite, string $unite): bool
    {
        $quantiteBase = $this->convertirVersBase($quantite, $unite);
        return $this->quantite_stock >= $quantiteBase;
    }

    /**
     * Retourne le stock dans une unité donnée.
     */
    public function getStockInUnite(string $unite): float
    {
        return $this->convertirDepuisBase($this->quantite_stock, $unite);
    }

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Un produit peut avoir plusieurs mouvements de stock.
     */
    public function mouvements()
    {
        return $this->hasMany(StockMouvement::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si le produit est actif.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Vérifie si le stock est bas (<= seuil alerte).
     */
    public function isStockBas(): bool
    {
        return $this->quantite_stock <= $this->seuil_alerte;
    }

    /**
     * Vérifie si le produit est en rupture.
     */
    public function isRupture(): bool
    {
        return $this->quantite_stock <= 0;
    }

    /**
     * Retourne le label du type de produit.
     */
    public function getTypeProduitLabelAttribute(): string
    {
        return match ($this->type_produit) {
            1 => 'Fourniture',
            2 => 'Livre',
            3 => 'Uniforme',
            4 => 'Alimentaire',
            default => 'Autre',
        };
    }
}
