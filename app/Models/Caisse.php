<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée.
     *
     * @var string
     */
    protected $table = 'caisses';

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'solde_initial',
        'solde_final',
        'date_ouverture',
        'date_cloture',
        'statut',
        'utilisateur_id',
        'responsable_id',
        'annee_id',
        'etat',
        // Champs d'écart
        'ecart_constate',
        'motif_ecart',
        'validateur_ecart_id',
        'date_validation_ecart',
        'statut_ecart',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'solde_initial' => 'float',
        'solde_final' => 'float',
        'ecart_constate' => 'float',
        'date_ouverture' => 'datetime',
        'date_cloture' => 'datetime',
        'date_validation_ecart' => 'datetime',
        'statut' => 'integer',
        'utilisateur_id' => 'integer',
        'responsable_id' => 'integer',
        'annee_id' => 'integer',
        'etat' => 'integer',
        'statut_ecart' => 'string',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────

    /**
     * Utilisateur qui a ouvert/fermé la caisse.
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Utilisateur responsable (ex: superviseur).
     */
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class);
    }

    /**
     * Utilisateur qui a validé l'écart.
     */
    public function validateurEcart()
    {
        return $this->belongsTo(User::class, 'validateur_ecart_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes utilitaires
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie si la caisse est active (non supprimée logiquement).
     */
    public function isActive(): bool
    {
        return $this->etat == 1;
    }

    /**
     * Vérifie si la caisse est ouverte.
     */
    public function isOuverte(): bool
    {
        return $this->statut == 1;
    }

    /**
     * Vérifie si la caisse a un écart non résolu.
     */
    public function hasEcart(): bool
    {
        return $this->ecart_constate != 0 && $this->statut_ecart !== 'valide';
    }

    /**
     * Libellé du statut de la caisse.
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            2 => 'Cloturée',
            1 => 'Ouverte',
            default => 'Inconnu',
        };
    }

    /**
     * Libellé du statut de l'écart.
     */
    public function getStatutEcartLabelAttribute(): string
    {
        return match ($this->statut_ecart) {
            'aucun'       => 'Aucun écart',
            'en_attente'  => 'En attente',
            'valide'      => 'Validé',
            'refuse'      => 'Refusé',
            'enquete'     => 'Enquête en cours',
            default       => 'Inconnu',
        };
    }
}
