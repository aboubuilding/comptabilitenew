<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeMouvementStock;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'date_stock', 'produit_id', 'magasin_id', 'bon_id',
        'quantite', 'type_mouvement',
        'prix_unitaire', 'valeur_mouvement',
        'libelle', 'motif',
        'utilisateur_id', 'source_type', 'source_id',
        'annee_id', 'etat',
    ];

    protected $casts = [
        'date_stock'       => 'date',
        'type_mouvement'   => TypeMouvementStock::class,
        'quantite'         => 'float',
        'prix_unitaire'    => 'float',
        'valeur_mouvement' => 'float',
    ];

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function bon(): BelongsTo
    {
        return $this->belongsTo(Bon::class, 'bon_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}