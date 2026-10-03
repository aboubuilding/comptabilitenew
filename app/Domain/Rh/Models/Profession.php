<?php

namespace App\Domain\Rh\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profession extends Model
{
    protected $table = 'professions';

    protected $fillable = [
        'libelle', 'salaire_base', 'annee_id', 'etat',
    ];

    protected $casts = [
        'salaire_base' => 'float',
    ];

    public function employes(): HasMany
    {
        return $this->hasMany(Employe::class, 'profession_id');
    }

    public function getNombreEmployesAttribute(): int
    {
        return $this->employes()->where('etat', 1)->count();
    }
}