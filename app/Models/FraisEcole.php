<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraisEcole extends Model
{
    use HasFactory;

    protected $table = 'frais_ecoles';

    protected $fillable = [
        'libelle',
        'montant',
        'type_paiement',
        'type_forfait',
        'niveau_id',
        'annee_id',
        'plan_echeancier_id',
        'etat',
    ];

    protected $casts = [
        'montant' => 'float',
        'type_paiement' => 'integer',
        'type_forfait' => 'integer',
        'niveau_id' => 'integer',
        'annee_id' => 'integer',
        'plan_echeancier_id' => 'integer',
        'etat' => 'integer',
    ];

    // Constantes
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    // Types de paiement
    const TYPE_FRAIS_INSCRIPTION = 1;
    const TYPE_FRAIS_SCOLARITE = 2;
    const TYPE_SERVICES = 3;
    const TYPE_PRODUIT = 4;
    const TYPE_LIVRE = 5;
    const TYPE_CAUTION = 6;
    const TYPE_BUS = 7;
    const TYPE_CANTINE = 8;
    const TYPE_AUTRES = 9;
    const TYPE_FRAIS_ASSURANCE = 10;
    const TYPE_FRAIS_EXTRA_SCOLAIRE = 11;
    const TYPE_FRAIS_EXAMEN = 12;

    // Types de forfait
    const FORFAIT_FIXE = 1;
    const FORFAIT_VARIABLE = 2;
    const FORFAIT_UNITE = 3;

    /**
     * Relation avec le niveau
     */
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    /**
     * Relation avec l'année
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    /**
     * Relation avec le plan d'échéancier
     */
    public function planEcheancier()
    {
        return $this->belongsTo(PlanEcheancier::class);
    }

    /**
     * Vérifier si le frais est payable en tranches
     */
    public function isPayableEnTranches(): bool
    {
        return $this->plan_echeancier_id !== null;
    }

    /**
     * Vérifier si le frais est actif
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Obtenir le libellé du type de paiement
     */
    public function getTypePaiementLabelAttribute(): string
    {
        return \App\Types\TypePaiement::getLabel($this->type_paiement);
    }

    /**
     * Scope pour les frais actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les frais avec échéancier
     */
    public function scopeAvecEcheancier($query)
    {
        return $query->whereNotNull('plan_echeancier_id');
    }

    /**
     * Scope pour les frais sans échéancier
     */
    public function scopeSansEcheancier($query)
    {
        return $query->whereNull('plan_echeancier_id');
    }

    /**
     * Scope pour les frais par niveau
     */
    public function scopeByNiveau($query, int $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    /**
     * Scope pour les frais par année
     */
    public function scopeByAnnee($query, int $anneeId)
    {
        return $query->where('annee_id', $anneeId);
    }
}
