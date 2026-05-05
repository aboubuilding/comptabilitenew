<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntretienVehicule extends Model
{
    protected $fillable = [
        'voiture_id', 'date_entretien', 'type_entretien', 'cout', 'kilometrage',
        'observations', 'effectue_par', 'annee_id'
    ];

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }

    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class, 'effectue_par');
    }
}