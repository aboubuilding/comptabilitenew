<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evenement extends Model
{
    use HasFactory;

    protected $table = 'evenements';

    protected $fillable = [
        'nom',
        'type',
        'date_evenement',
        'participation',
        'capacite',
        'description',
        'annee_id',
        'etat',
    ];

    protected $casts = [
        'date_evenement' => 'date',
        'participation' => 'decimal:2',
        'capacite' => 'integer',
        'annee_id' => 'integer',
        'etat' => 'integer',
    ];

    // Constantes
    const ETAT_ACTIF = 1;
    const ETAT_INACTIF = 0;

    // Types d'événements
    const TYPE_EXCURSION = 'excursion';
    const TYPE_VOYAGE = 'voyage';
    const TYPE_SORTIE_PEDAGOGIQUE = 'sortie_pedagogique';
    const TYPE_COMPETITION = 'competition';
    const TYPE_AUTRE = 'autre';

    /**
     * Obtenir la liste des types d'événements
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_EXCURSION,
            self::TYPE_VOYAGE,
            self::TYPE_SORTIE_PEDAGOGIQUE,
            self::TYPE_COMPETITION,
            self::TYPE_AUTRE,
        ];
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EXCURSION => 'Excursion',
            self::TYPE_VOYAGE => 'Voyage',
            self::TYPE_SORTIE_PEDAGOGIQUE => 'Sortie Pédagogique',
            self::TYPE_COMPETITION => 'Compétition',
            self::TYPE_AUTRE => 'Autre',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la classe CSS du type
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EXCURSION => 'badge-primary',
            self::TYPE_VOYAGE => 'badge-info',
            self::TYPE_SORTIE_PEDAGOGIQUE => 'badge-success',
            self::TYPE_COMPETITION => 'badge-warning',
            self::TYPE_AUTRE => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Obtenir l'icône du type
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_EXCURSION => 'fa-hiking',
            self::TYPE_VOYAGE => 'fa-plane',
            self::TYPE_SORTIE_PEDAGOGIQUE => 'fa-school',
            self::TYPE_COMPETITION => 'fa-trophy',
            self::TYPE_AUTRE => 'fa-ellipsis-h',
            default => 'fa-calendar',
        };
    }

    /**
     * Vérifier si l'événement est actif
     */
    public function isActive(): bool
    {
        return $this->etat === self::ETAT_ACTIF;
    }

    /**
     * Vérifier si l'événement est passé
     */
    public function isPast(): bool
    {
        return $this->date_evenement < now();
    }

    /**
     * Vérifier si l'événement est à venir
     */
    public function isUpcoming(): bool
    {
        return $this->date_evenement > now();
    }

    /**
     * Vérifier si l'événement est aujourd'hui
     */
    public function isToday(): bool
    {
        return $this->date_evenement->isToday();
    }

    /**
     * Vérifier si l'événement a encore de la place
     */
    public function hasAvailableSlots(): bool
    {
        if (!$this->capacite) {
            return true;
        }
        // À adapter selon votre système d'inscription
        return true;
    }

    /**
     * Relation avec l'année
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    /**
     * Scope pour les événements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('etat', self::ETAT_ACTIF);
    }

    /**
     * Scope pour les événements par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour les événements à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date_evenement', '>=', now()->startOfDay());
    }

    /**
     * Scope pour les événements passés
     */
    public function scopePast($query)
    {
        return $query->where('date_evenement', '<', now()->startOfDay());
    }

    /**
     * Scope pour les événements par année
     */
    public function scopeByAnnee($query, int $anneeId)
    {
        return $query->where('annee_id', $anneeId);
    }
}
