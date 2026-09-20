<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    use HasFactory;

    protected $table = 'niveaux';

    protected $fillable = ['libelle', 'description', 'numero_ordre', 'cycle_id', 'etat'];

    protected $casts = [
        'numero_ordre' => 'integer',
        'cycle_id'     => 'integer',
        'etat'         => 'integer',
    ];

    public const ETAT_ACTIF   = 1;
    public const ETAT_INACTIF = 0;

    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    public function scopeOrdonnes($query)
    {
        return $query->orderBy('numero_ordre');
    }
}