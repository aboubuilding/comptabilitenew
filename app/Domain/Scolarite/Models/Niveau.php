<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Niveau extends Model
{
    protected $table = 'niveaux';

    protected $fillable = ['libelle', 'description', 'numero_ordre', 'cycle_id', 'etat'];

    protected $casts = [
        'numero_ordre' => 'integer',
        'cycle_id'     => 'integer',
        'etat'         => 'integer',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}