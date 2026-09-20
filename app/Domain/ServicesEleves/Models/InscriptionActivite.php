<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\Scolarite\Models\Eleve;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscriptionActivite extends Model
{
    use HasFactory;

    protected $table = 'inscriptions_activites';

    protected $fillable = ['eleve_id', 'activite_id', 'annee_id', 'date_inscription', 'montant_du', 'statut', 'etat'];

    protected $casts = [
        'date_inscription' => 'date',
        'montant_du'       => 'float',
        'statut'           => 'integer',
        'etat'             => 'integer',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function activite()
    {
        return $this->belongsTo(Activite::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}