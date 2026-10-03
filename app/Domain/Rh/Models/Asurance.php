<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue des assurances (assureurs / polices).
 * Table : asurances
 */
class Asurance extends Model
{
    protected $table = 'asurances';

    protected $fillable = [
        'nom_assureur',
        'police_assurance',
        'montant_annuel',
        'annee_id',
        'etat',
    ];

    protected $casts = [
        'montant_annuel' => 'decimal:2',
        'etat'           => 'integer',
        'annee_id'       => 'integer',
    ];

    /**
     * Souscriptions rattachées à cette assurance.
     */
    public function assures(): HasMany
    {
        return $this->hasMany(Assure::class, 'assurance_id');
    }

    /**
     * Scope actifs.
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}