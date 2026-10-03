<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeMagasin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Magasin extends Model
{
    protected $table = 'magasins';

    protected $fillable = [
        'libelle', 'responsable', 'description', 'adresse',
        'telephone', 'type', 'etat',
    ];

    protected $casts = [
        'type' => TypeMagasin::class,
    ];

    public function stocksActuels(): HasMany
    {
        return $this->hasMany(StockActuel::class, 'magasin_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(Stock::class, 'magasin_id');
    }

    public function bons(): HasMany
    {
        return $this->hasMany(Bon::class, 'magasin_id');
    }

    public function bonsEnEntree(): HasMany
    {
        return $this->hasMany(Bon::class, 'magasin_destination_id');
    }

    public function isEntrepot(): bool
    {
        return $this->type === TypeMagasin::ENTREPOT;
    }

    public function isBoutique(): bool
    {
        return $this->type === TypeMagasin::BOUTIQUE;
    }
}