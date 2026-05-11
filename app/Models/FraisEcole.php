<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraisEcole extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'frais_ecoles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'montant',
        'type_paiement',
        'type_forfait',
        'niveau_id',
        'annee_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant'        => 'float',
        'type_paiement'  => 'integer',   // tinyInteger
        'type_forfait'   => 'integer',   // tinyInteger
        'niveau_id'      => 'integer',
        'annee_id'       => 'integer',
        'etat'           => 'boolean',   // 0/1
    ];

    // ===== CONSTANTES POUR `type_paiement` =====
    const PAIEMENT_UNIQUE     = 1;
    const PAIEMENT_MENSUEL    = 2;
    const PAIEMENT_TRIMESTRIEL= 3;
    const PAIEMENT_SEMESTRIEL = 4;

    // ===== CONSTANTES POUR `type_forfait` =====
    const FORFAIT_NORMAL      = 1;
    const FORFAIT_REDUIT      = 2;
    const FORFAIT_BOURSE      = 3;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec le niveau (table `niveaux`)
     */
    public function niveau()
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    /**
     * Relation avec l'année scolaire (table `annees`)
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le frais est actif
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer/désactiver le frais
     */
    public function setActif(bool $actif): void
    {
        $this->etat = $actif ? self::ETAT_ACTIF : self::ETAT_INACTIF;
        $this->save();
    }

    /**
     * Libellé du type de paiement
     */
    public function getTypePaiementLabelAttribute(): string
    {
        $labels = [
            self::PAIEMENT_UNIQUE       => 'Unique',
            self::PAIEMENT_MENSUEL      => 'Mensuel',
            self::PAIEMENT_TRIMESTRIEL  => 'Trimestriel',
            self::PAIEMENT_SEMESTRIEL   => 'Semestriel',
        ];
        return $labels[$this->type_paiement] ?? 'Non défini';
    }

    /**
     * Libellé du type de forfait
     */
    public function getTypeForfaitLabelAttribute(): string
    {
        $labels = [
            self::FORFAIT_NORMAL => 'Normal',
            self::FORFAIT_REDUIT => 'Réduit',
            self::FORFAIT_BOURSE => 'Bourse',
        ];
        return $labels[$this->type_forfait] ?? 'Non défini';
    }
}
