<?php

namespace App\Domain\Scolarite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nationalite extends Model
{
    use HasFactory;

    protected $table = 'nationalites';

    protected $fillable = ['libelle', 'etat'];

    protected $casts = ['etat' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}