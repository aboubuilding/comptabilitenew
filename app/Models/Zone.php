<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'zones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'libelle',
        'description',
        'tarif_base',
        'ordre',
        'couleur',
        'chauffeur_id',
        'voiture_id',
        'annee_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tarif_base' => 'decimal:2',
        'ordre'      => 'integer',
        'etat'       => 'boolean',
    ];

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec le chauffeur.
     */
    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class, 'chauffeur_id');
    }

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
     * Vérifier si la zone est active.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer la zone.
     */
    public function activer(): void
    {
        $this->etat = self::ETAT_ACTIF;
        $this->save();
    }

    /**
     * Désactiver la zone.
     */
    public function desactiver(): void
    {
        $this->etat = self::ETAT_INACTIF;
        $this->save();
    }

    /**
     * Obtenir le tarif formaté.
     */
    public function getTarifFormattedAttribute(): string
    {
        return number_format($this->tarif_base, 2, ',', ' ') . ' FCFA';
    }
}
