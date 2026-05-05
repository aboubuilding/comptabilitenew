<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffectationVehicule extends Model
{
    protected $fillable = [
        'voiture_id', 'chauffeur_id', 'date_debut', 'date_fin', 'motif', 'type_affectation', 'annee_id'
    ];

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }

    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class);
    }
}