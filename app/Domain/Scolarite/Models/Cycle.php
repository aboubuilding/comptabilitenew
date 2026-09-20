<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    protected $table = 'cycles';

    protected $fillable = ['libelle', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public const ETAT_ACTIF   = 1;
    public const ETAT_INACTIF = 0;

    public function niveaux()
    {
        return $this->hasMany(Niveau::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }
}