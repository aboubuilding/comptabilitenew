<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'cycles';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations (exemples, ajustez selon votre schéma)
    // ─────────────────────────────────────────────────────────────

    /**
     * Un cycle peut avoir plusieurs niveaux.
     */
    public function niveaux()
    {
        return $this->hasMany(Niveau::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si le cycle est actif.
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }
}
