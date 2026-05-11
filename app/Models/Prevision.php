<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prevision extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'previsions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'type',
        'montant',
        'date_prevision',
        'date_fin',
        'periode',
        'annee_id',
        'categorie_id',
        'commentaire',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant'        => 'decimal:2',
        'date_prevision' => 'date',
        'date_fin'       => 'date',
        'etat'           => 'boolean',
    ];

    // ===== CONSTANTES =====
    const TYPE_RECETTE  = 'recette';
    const TYPE_DEPENSE  = 'depense';

    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    /**
     * Relation avec la catégorie (si utilisée).
     */
    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    public function isRecette(): bool
    {
        return $this->type === self::TYPE_RECETTE;
    }

    public function isDepense(): bool
    {
        return $this->type === self::TYPE_DEPENSE;
    }

    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isRecette() ? 'Recette' : 'Dépense';
    }
}
