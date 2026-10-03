<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Logistique\Models\Magasin;
use App\Domain\ServicesEleves\Types\TypeRepas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        'libelle', 'description', 'date_service', 'type_repas',
        'quantite_prevue', 'quantite_reellement',
        'cout_total_prevu', 'cout_total_reel',
        'inscription_cantine_id', 'magasin_id',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'type_repas'         => TypeRepas::class,
        'date_service'       => 'date',
        'cout_total_prevu'   => 'float',
        'cout_total_reel'    => 'float',
    ];

    public function inscriptionCantine(): BelongsTo
    {
        return $this->belongsTo(InscriptionCantine::class, 'inscription_cantine_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    /** Composition du menu (via menu_produits). */
    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Logistique\Models\Produit::class,
            'menu_produits',
            'menu_id',
            'produit_id'
        )->withPivot(['quantite', 'cout_unitaire', 'cout_total'])
         ->withTimestamps();
    }

    public function menuProduits(): HasMany
    {
        return $this->hasMany(MenuProduit::class, 'menu_id');
    }

    public function preparations(): HasMany
    {
        return $this->hasMany(PreparationRepas::class, 'menu_id');
    }

    /** Recalcule le coût prévisionnel depuis les produits. */
    public function recalculerCoutPrevu(): float
    {
        $total = round((float) $this->menuProduits()->sum('cout_total'), 2);
        $this->update(['cout_total_prevu' => $total]);
        return $total;
    }
}