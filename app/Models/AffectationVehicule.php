<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffectationVehicule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affectations_vehicules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'voiture_id',
        'chauffeur_id',
        'date_debut',
        'date_fin',
        'motif',
        'type_affectation',
        'annee_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'type_affectation' => 'integer',  // tinyInteger en base
    ];

    // ===== CONSTANTES POUR `type_affectation` =====
    const TYPE_PERMANENTE   = 1;
    const TYPE_TEMPORAIRE   = 2;

    // ===== RELATIONS =====
    /**
     * Relation avec la voiture.
     */
    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'voiture_id');
    }

    /**
     * Relation avec le chauffeur.
     */
    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_id');
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
     * Vérifier si l'affectation est permanente.
     */
    public function isPermanente(): bool
    {
        return $this->type_affectation == self::TYPE_PERMANENTE;
    }

    /**
     * Vérifier si l'affectation est temporaire.
     */
    public function isTemporaire(): bool
    {
        return $this->type_affectation == self::TYPE_TEMPORAIRE;
    }

    /**
     * Vérifier si l'affectation est terminée (date_fin dans le passé).
     */
    public function estTerminee(): bool
    {
        return $this->date_fin && $this->date_fin->isPast();
    }

    /**
     * Vérifier si l'affectation est en cours.
     */
    public function estEnCours(): bool
    {
        return $this->date_debut->isPast() &&
            (!$this->date_fin || $this->date_fin->isFuture());
    }

    /**
     * Libellé du type d'affectation.
     */
    public function getTypeLabelAttribute(): string
    {
        return [
            self::TYPE_PERMANENTE => 'Permanente',
            self::TYPE_TEMPORAIRE => 'Temporaire',
        ][$this->type_affectation] ?? 'Indéfini';
    }
}
