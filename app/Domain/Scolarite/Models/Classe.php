<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classe extends Model
{
    protected $table = 'classes';

    protected $fillable = ['libelle', 'emplacement', 'cycle_id', 'niveau_id', 'annee_id', 'etat'];

    protected $casts = [
        'cycle_id'  => 'integer',
        'niveau_id' => 'integer',
        'annee_id'  => 'integer',
        'etat'      => 'integer',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}