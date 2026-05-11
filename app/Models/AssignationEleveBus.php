<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignationEleveBus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'assignations_eleves_bus';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'abonnement_bus_id',
        'voiture_id',
        'zone_id',
        'date_debut',
        'date_fin',
        'sens',
        'motif',
        'statut',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'sens'       => 'integer',
        'statut'     => 'integer',
    ];

    // ===== CONSTANTES POUR `sens` =====
    const SENS_ALLER        = 1;
    const SENS_RETOUR       = 2;
    const SENS_ALLER_RETOUR = 3;

    // ===== CONSTANTES POUR `statut` =====
    const STATUT_ACTIF   = 1;
    const STATUT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'abonnement bus.
     */
    public function abonnementBus()
    {
        return $this->belongsTo(AbonnementBus::class, 'abonnement_bus_id');
    }

    /**
     * Relation avec la voiture (bus).
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

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si l'assignation est active.
     */
    public function isActif(): bool
    {
        return $this->statut == self::STATUT_ACTIF;
    }

    /**
     * Vérifier si l'assignation est en cours (à la date du jour).
     */
    public function estEnCours(): bool
    {
        $today = now()->startOfDay();
        return $this->date_debut <= $today &&
            ($this->date_fin === null || $this->date_fin >= $today) &&
            $this->isActif();
    }

    /**
     * Vérifier si le sens est Aller.
     */
    public function estAller(): bool
    {
        return $this->sens == self::SENS_ALLER;
    }

    /**
     * Vérifier si le sens est Retour.
     */
    public function estRetour(): bool
    {
        return $this->sens == self::SENS_RETOUR;
    }

    /**
     * Vérifier si le sens est Aller-Retour.
     */
    public function estAllerRetour(): bool
    {
        return $this->sens == self::SENS_ALLER_RETOUR;
    }

    /**
     * Libellé du sens.
     */
    public function getSensLabelAttribute(): string
    {
        return [
            self::SENS_ALLER        => 'Aller',
            self::SENS_RETOUR       => 'Retour',
            self::SENS_ALLER_RETOUR => 'Aller-Retour',
        ][$this->sens] ?? 'Indéfini';
    }
}
