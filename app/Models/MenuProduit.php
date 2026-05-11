<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuProduit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'menu_produits';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'menu_id',
        'produit_id',
        'quantite',
        'cout_unitaire',
        'cout_total',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantite' => 'decimal:3',
        'cout_unitaire' => 'decimal:2',
        'cout_total' => 'decimal:2',
    ];

    // ===== RELATIONS =====

    /**
     * Relation avec le menu parent.
     */
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    /**
     * Relation avec le produit.
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    // ===== MÉTHODES UTILITAIRES =====

    /**
     * Calculer et mettre à jour le coût total (quantité * prix unitaire).
     */
    public function calculerCoutTotal(): void
    {
        if ($this->quantite && $this->cout_unitaire) {
            $this->cout_total = $this->quantite * $this->cout_unitaire;
        }
    }
}
