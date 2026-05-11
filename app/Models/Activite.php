<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activite extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'activites';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'description',
        'montant',
        'annee_id',
        'niveau_id',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'montant' => 'float',
        'annee_id' => 'integer',
        'niveau_id' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Une activité appartient à une année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    /**
     * Une activité peut être proposée à un niveau spécifique.
     */
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'activité est active.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }
}
