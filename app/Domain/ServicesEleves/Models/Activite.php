<?php

namespace App\Domain\ServicesEleves\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    use HasFactory;

    protected $table = 'activites';

    protected $fillable = [
        'libelle', 'description', 'montant', 'annee_id', 'niveau_id',
        'type', 'encadreur', 'contact_encadreur', 'etat',
    ];

    protected $casts = [
        'montant' => 'float',
        'etat'    => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}