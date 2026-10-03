<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $table = 'cycles';

    protected $fillable = ['libelle', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class, 'cycle_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}