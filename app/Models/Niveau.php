<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Niveau extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'niveaux';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'description',
        'numero_ordre',
        'cycle_id',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'numero_ordre' => 'integer',
        'cycle_id' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Un niveau appartient à un cycle.
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Un niveau peut avoir plusieurs classes.
     */
    public function classes()
    {
        return $this->hasMany(Classe::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si le niveau est actif.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }
}
