<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chauffeur extends Model
{
    protected $fillable = [
        'nom', 'prenom', 'permis_conduire', 'date_validite_permis', 'telephone',
        'email', 'adresse', 'statut', 'annee_id'
    ];

    public function affectations()
    {
        return $this->hasMany(AffectationVehicule::class);
    }
}