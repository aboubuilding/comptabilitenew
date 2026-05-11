<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Espace extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'espaces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom_famille',
        'annee_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'annee_id' => 'integer',
        'etat'     => 'boolean',   // puisque valeur 0/1 en base
    ];

    // ===== CONSTANTES POUR LE CHAMP `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'année (si la table `annees` existe)
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si l'espace est actif
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer l'espace
     */
    public function activer(): void
    {
        $this->etat = self::ETAT_ACTIF;
        $this->save();
    }

    /**
     * Désactiver l'espace
     */
    public function desactiver(): void
    {
        $this->etat = self::ETAT_INACTIF;
        $this->save();
    }
}
