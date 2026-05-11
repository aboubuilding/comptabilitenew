<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockActuel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_actuel';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'produit_id',
        'magasin_id',
        'quantite',
        'seuil_alerte',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantite'     => 'float',
        'seuil_alerte' => 'float',
    ];

    // ===== RELATIONS =====
    /**
     * Relation avec le produit.
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /**
     * Relation avec le magasin.
     */
    public function magasin()
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le stock est inférieur ou égal au seuil d'alerte.
     */
    public function estEnAlerte(): bool
    {
        if ($this->seuil_alerte === null) {
            return false;
        }
        return $this->quantite <= $this->seuil_alerte;
    }
}
