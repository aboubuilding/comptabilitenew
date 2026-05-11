<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annee extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'annees';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'date_rentree',
        'date_fin',
        'date_ouverture_inscription',
        'date_fermeture_reinscription',
        'statut_annee',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_rentree' => 'date',
        'date_fin' => 'date',
        'date_ouverture_inscription' => 'date',
        'date_fermeture_reinscription' => 'date',
        'statut_annee' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations (exemples, à adapter selon vos besoins)
    // ─────────────────────────────────────────────────────────────

    /**
     * Une année scolaire peut avoir plusieurs inscriptions.
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Une année scolaire peut avoir plusieurs périodes.
     */
    public function periodes()
    {
        return $this->hasMany(Periode::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si l'année est active (statut_annee = 1 et état = 1).
     */
    public function isActive(): bool
    {
        return $this->etat == 1 && $this->statut_annee == 1;
    }

    /**
     * Vérifie si la date actuelle se trouve dans l'année scolaire.
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->date_rentree, $this->date_fin);
    }
}
