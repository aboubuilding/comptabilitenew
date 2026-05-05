<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voiture extends Model
{
    protected $fillable = [
        'marque', 'modele', 'plaque', 'nombre_place', 'annee_fabrication', 'couleur',
        'numero_chassis', 'date_achat', 'prix_achat', 'fournisseur', 'kilometrage_actuel',
        'statut', 'annee_id', 'etat'
    ];

    protected $casts = [
        'date_achat' => 'date',
        'prix_achat' => 'decimal:2',
        'kilometrage_actuel' => 'integer',
    ];

    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    public function affectations()
    {
        return $this->hasMany(AffectationVehicule::class);
    }

    public function entretiens()
    {
        return $this->hasMany(EntretienVehicule::class);
    }

    public function carburants()
    {
        return $this->hasMany(CarburantVehicule::class);
    }

    public function assurances()
    {
        return $this->hasMany(AssuranceVehicule::class);
    }

    // Accessoires
    public function getStatutLabelAttribute()
    {
        return match($this->statut) {
            1 => 'Disponible',
            2 => 'En maintenance',
            3 => 'Sorti',
            4 => 'Réformé',
            default => 'Inconnu',
        };
    }
}