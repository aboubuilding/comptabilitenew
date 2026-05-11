<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tranche extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tranches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'date_butoire',
        'frais_ecole_id',
        'type_frais',
        'taux',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_butoire'    => 'date',
        'frais_ecole_id'  => 'integer',
        'type_frais'      => 'integer',
        'taux'            => 'integer',
        'etat'            => 'boolean',   // 0/1
    ];

    // ===== CONSTANTES POUR `type_frais` =====
    const TYPE_FRAIS_INSCRIPTION   = 1;
    const TYPE_FRAIS_SCOLARITE     = 2;
    const TYPE_FRAIS_TRANSPORT     = 3;
    const TYPE_FRAIS_CANTINE       = 4;
    const TYPE_FRAIS_DIVERS        = 5;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec le modèle FraisEcole
     */
    public function fraisEcole()
    {
        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si la tranche est active
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer/désactiver la tranche
     */
    public function setActif(bool $actif): void
    {
        $this->etat = $actif ? self::ETAT_ACTIF : self::ETAT_INACTIF;
        $this->save();
    }

    /**
     * Libellé du type de frais
     */
    public function getTypeFraisLabelAttribute(): string
    {
        $labels = [
            self::TYPE_FRAIS_INSCRIPTION => 'Inscription',
            self::TYPE_FRAIS_SCOLARITE   => 'Scolarité',
            self::TYPE_FRAIS_TRANSPORT   => 'Transport',
            self::TYPE_FRAIS_CANTINE     => 'Cantine',
            self::TYPE_FRAIS_DIVERS      => 'Divers',
        ];
        return $labels[$this->type_frais] ?? 'Non défini';
    }

    /**
     * Vérifier si la date butoire est dépassée
     */
    public function isDepasse(): bool
    {
        if (!$this->date_butoire) {
            return false;
        }
        return now()->startOfDay()->gt($this->date_butoire);
    }
}
