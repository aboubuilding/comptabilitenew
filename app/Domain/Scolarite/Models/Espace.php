<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Espace familial (fratrie).
 */
class Espace extends Model
{
    protected $table = 'espaces';

    protected $fillable = ['nom_famille', 'annee_id', 'etat'];

    protected $casts = [
        'annee_id' => 'integer',
        'etat'     => 'integer',
    ];

    public function eleves(): HasMany
    {
        return $this->hasMany(Eleve::class, 'espace_id');
    }

    public function parents(): HasMany
    {
        return $this->hasMany(ParentEleve::class, 'espace_id');
    }

    /** Comptes d'accès au portail parent. */
    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class, 'espace_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}