<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Model;

class Nationalite extends Model
{
    protected $table = 'nationalites';

    protected $fillable = ['libelle', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public function scopeActif($query)
    {
        return $query->where('etat', 1);
    }
}