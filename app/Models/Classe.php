<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'emplacement',
        'cycle_id',
        'niveau_id',
        'annee_id',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cycle_id' => 'integer',
        'niveau_id' => 'integer',
        'annee_id' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations (exemples, ajustez selon votre schéma)
    // ─────────────────────────────────────────────────────────────

    /**
     * Une classe appartient à un cycle.
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Une classe appartient à un niveau.
     */
    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    /**
     * Une classe appartient à une année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthode utilitaire
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si la classe est active.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }
}
