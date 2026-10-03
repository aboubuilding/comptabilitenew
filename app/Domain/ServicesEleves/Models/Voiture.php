<?php

namespace App\Domain\ServicesEleves\Models;

use App\Domain\ServicesEleves\Types\StatutVehicule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voiture extends Model
{
    protected $table = 'voitures';

    protected $fillable = [
        'marque', 'modele', 'plaque', 'nombre_place', 'annee_fabrication',
        'numero_chassis', 'date_achat', 'prix_achat', 'fournisseur',
        'kilometrage_actuel', 'statut', 'annee_id', 'etat',
    ];

    protected $casts = [
        'statut'            => StatutVehicule::class,
        'date_achat'        => 'date',
        'prix_achat'        => 'float',
        'kilometrage_actuel'=> 'float',
    ];

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationVehicule::class, 'voiture_id');
    }

    public function entretiens(): HasMany
    {
        return $this->hasMany(EntretienVehicule::class, 'voiture_id');
    }

    public function carburants(): HasMany
    {
        return $this->hasMany(CarburantVehicule::class, 'voiture_id');
    }

    public function assurances(): HasMany
    {
        return $this->hasMany(AssuranceVehicule::class, 'voiture_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'voiture_id');
    }

    /** Chauffeur actuellement affecté (affectation active). */
    public function chauffeurActuel()
    {
        $affectation = $this->affectations()
            ->whereNull('date_fin')
            ->orWhere('date_fin', '>=', now()->toDateString())
            ->with('chauffeur')
            ->orderByDesc('date_debut')
            ->first();

        return $affectation?->chauffeur;
    }

    /** Consommation totale de carburant (litres). */
    public function getCarburantTotalLitreAttribute(): float
    {
        return round((float) $this->carburants->sum('quantite_litres'), 2);
    }

    /** Coût total carburant. */
    public function getCoutCarburantAttribute(): float
    {
        return round((float) $this->carburants->sum('montant_total'), 2);
    }

    /** Coût total entretien. */
    public function getCoutEntretienAttribute(): float
    {
        return round((float) $this->entretiens->sum('cout'), 2);
    }

    /** Assurance active (non expirée). */
    public function assuranceActive()
    {
        return $this->assurances()
            ->where('date_fin', '>=', now()->toDateString())
            ->orderByDesc('date_fin')
            ->first();
    }
}