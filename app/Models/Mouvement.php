<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mouvement extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'mouvements';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'beneficiaire',
        'motif',
        'date_mouvement',
        'montant',
        'type_mouvement',
        'caisse_id',
        'utilisateur_id',
        'paiement_id',
        'depense_id',
        'annee_id',
        'file',
        'statut_mouvement',
        'etat',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_mouvement' => 'date',
        'montant' => 'float',
        'type_mouvement' => 'integer',
        'caisse_id' => 'integer',
        'utilisateur_id' => 'integer',
        'paiement_id' => 'integer',
        'depense_id' => 'integer',
        'annee_id' => 'integer',
        'statut_mouvement' => 'integer',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function depense()
    {
        return $this->belongsTo(Depense::class);
    }

    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Type de mouvement : 1 = entrée, 2 = sortie.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_mouvement) {
            1 => 'Entrée',
            2 => 'Sortie',
            default => 'Inconnu',
        };
    }
}
