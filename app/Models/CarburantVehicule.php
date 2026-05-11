<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarburantVehicule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'carburant_vehicules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voiture_id',
        'date_plein',
        'quantite_litres',
        'prix_unitaire',
        'montant_total',
        'kilometrage',
        'station_service',
        'facture',
        'annee_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_plein'      => 'date',
        'quantite_litres' => 'decimal:2',
        'prix_unitaire'   => 'decimal:2',
        'montant_total'   => 'decimal:2',
        'kilometrage'     => 'integer',
    ];

    // ===== RELATIONS =====
    /**
     * Relation avec la voiture.
     */
    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Calculer automatiquement le montant total (quantité * prix unitaire).
     */
    public function calculerMontantTotal(): void
    {
        $this->montant_total = $this->quantite_litres * $this->prix_unitaire;
    }

    /**
     * Formater le montant total pour l'affichage.
     */
    public function getMontantFormattedAttribute(): string
    {
        return number_format($this->montant_total, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Formater la quantité en litres.
     */
    public function getQuantiteFormattedAttribute(): string
    {
        return number_format($this->quantite_litres, 2, ',', ' ') . ' L';
    }
}
