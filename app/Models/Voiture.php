<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voiture extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'voitures';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'marque',
        'modele',
        'plaque',
        'nombre_place',
        'annee_fabrication',
        'couleur',
        'numero_chassis',
        'date_achat',
        'prix_achat',
        'fournisseur',
        'kilometrage_actuel',
        'statut',
        'annee_id',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nombre_place' => 'integer',
        'annee_fabrication' => 'integer',
        'date_achat' => 'date',
        'prix_achat' => 'decimal:2',
        'kilometrage_actuel' => 'float',
        'statut' => 'integer',
        'annee_id' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Une voiture appartient à une année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si la voiture est active (non supprimée logiquement).
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Vérifie si la voiture est disponible (statut = 1).
     */
    public function isDisponible(): bool
    {
        return $this->statut == 1;
    }

    /**
     * Libellé du statut de la voiture.
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            1 => 'Disponible',
            2 => 'En maintenance',
            3 => 'Sorti',
            4 => 'Réformé',
            default => 'Inconnu',
        };
    }
}
