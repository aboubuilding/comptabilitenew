<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssuranceVehicule extends Model
{
    protected $fillable = [
        'voiture_id', 'compagnie_assurance', 'numero_contrat', 'date_debut', 'date_fin',
        'prime', 'type_assurance'
    ];

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }
}