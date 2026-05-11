<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepenseVoiture extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'depense_voitures';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'date_depense',
        'montant',
        'voiture_id',
        'zone_id',
        'type_depense',
        'annee_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_depense' => 'date',
        'montant'      => 'float',
        'type_depense' => 'integer',
        'etat'         => 'boolean',
    ];

    // ===== CONSTANTES POUR `type_depense` =====
    const TYPE_CARBURANT     = 1;
    const TYPE_ENTRETIEN     = 2;
    const TYPE_REPARATION    = 3;
    const TYPE_ASSURANCE     = 4;
    const TYPE_AMENDE        = 5;
    const TYPE_AUTRE         = 6;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec la voiture.
     */
    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    /**
     * Relation avec la zone.
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
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
     * Vérifier si la dépense est active.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Libellé du type de dépense.
     */
    public function getTypeDepenseLabelAttribute(): string
    {
        $labels = [
            self::TYPE_CARBURANT  => 'Carburant',
            self::TYPE_ENTRETIEN  => 'Entretien',
            self::TYPE_REPARATION => 'Réparation',
            self::TYPE_ASSURANCE  => 'Assurance',
            self::TYPE_AMENDE     => 'Amende',
            self::TYPE_AUTRE      => 'Autre',
        ];
        return $labels[$this->type_depense] ?? 'Indéfini';
    }
}
