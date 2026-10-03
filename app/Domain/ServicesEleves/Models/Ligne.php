<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ligne extends Model
{
    protected $table = 'lignes';

    protected $fillable = [
        'libelle', 'prix_minimal', 'prix_plafond', 'annee_id', 'etat',
    ];

    protected $casts = [
        'prix_minimal' => 'float',
        'prix_plafond' => 'float',
    ];

    public function abonnements(): HasMany
    {
        return $this->hasMany(AbonnementBus::class, 'ligne_id');
    }
}