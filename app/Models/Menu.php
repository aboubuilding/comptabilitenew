<?php

namespace App\Models;

class Menu extends Model
{
    protected $fillable = [
        'libelle', 'description', 'date_service', 'type_repas', 'quantite_prevue',
        'quantite_reellement', 'cout_total_prevu', 'cout_total_reel',
        'inscription_cantine_id', 'annee_id', 'etat'
    ];

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'menu_produits')
                    ->withPivot('quantite', 'cout_unitaire', 'cout_total')
                    ->withTimestamps();
    }

    public function preparations()
    {
        return $this->hasMany(PreparationRepas::class);
    }

    public function calculateCoutTotal()
    {
        $total = $this->produits->sum(function ($produit) {
            return $produit->pivot->cout_total;
        });
        $this->cout_total_prevu = $total;
        $this->save();
        return $total;
    }

    public function getCoutParPartAttribute()
    {
        $parts = $this->quantite_reellement ?? $this->quantite_prevue;
        if ($parts <= 0) return 0;
        $cout = $this->cout_total_reel ?? $this->cout_total_prevu;
        return $cout / $parts;
    }
}