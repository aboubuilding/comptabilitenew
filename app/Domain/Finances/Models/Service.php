<?php

namespace App\Domain\Finances\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Placé en domaine Finances (catalogue de prestations facturables), pas
 * Logistique — à revoir si un domaine plus adapté se dessine ailleurs.
 */
class Service extends Model
{
    use HasFactory;

    protected $table = 'services';

    protected $fillable = ['libelle', 'description', 'prix_unitaire', 'etat'];

    protected $casts = [
        'prix_unitaire' => 'float',
        'etat'          => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}