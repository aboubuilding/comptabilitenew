<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarburantVehicule extends Model
{
    protected $fillable = [
        'voiture_id', 'date_plein', 'quantite_litres', 'prix_unitaire', 'montant_total',
        'kilometrage', 'station_service', 'facture', 'annee_id'
    ];

    public function voiture()
    {
        return $this->belongsTo(Voiture::class);
    }
}