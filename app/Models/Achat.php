<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'achats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date_achat',
        'nom_acheteur',
        'reference',
        'bon_commande',
        'commentaire',
        'fournisseur_id',
        'annee_id',
        'statut_paiement',
        'statut_livraison',
        'montant_total',
        'type_achat',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_achat'       => 'date',
        'montant_total'    => 'decimal:2',
        'statut_paiement'  => 'integer',
        'statut_livraison' => 'integer',
        'type_achat'       => 'integer',
        'etat'             => 'boolean',
    ];

    // ===== CONSTANTES POUR `statut_paiement` =====
    const STATUT_PAIEMENT_IMPAYE   = 0;
    const STATUT_PAIEMENT_PARTIEL  = 1;
    const STATUT_PAIEMENT_PAYE     = 2;

    // ===== CONSTANTES POUR `statut_livraison` =====
    const STATUT_LIVRAISON_EN_ATTENTE = 0;
    const STATUT_LIVRAISON_PARTIELLE  = 1;
    const STATUT_LIVRAISON_RECUE      = 2;

    // ===== CONSTANTES POUR `type_achat` =====
    const TYPE_ACHAT_CANTINE  = 1;
    const TYPE_ACHAT_BOUTIQUE = 2;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec le fournisseur.
     */
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    public function isPaiementPaye(): bool
    {
        return $this->statut_paiement == self::STATUT_PAIEMENT_PAYE;
    }

    public function isLivraisonRecue(): bool
    {
        return $this->statut_livraison == self::STATUT_LIVRAISON_RECUE;
    }

    public function isCantine(): bool
    {
        return $this->type_achat == self::TYPE_ACHAT_CANTINE;
    }

    public function isBoutique(): bool
    {
        return $this->type_achat == self::TYPE_ACHAT_BOUTIQUE;
    }

    public function getStatutPaiementLabelAttribute(): string
    {
        return [
            self::STATUT_PAIEMENT_IMPAYE  => 'Impayé',
            self::STATUT_PAIEMENT_PARTIEL => 'Paiement partiel',
            self::STATUT_PAIEMENT_PAYE    => 'Payé',
        ][$this->statut_paiement] ?? 'Indéfini';
    }

    public function getStatutLivraisonLabelAttribute(): string
    {
        return [
            self::STATUT_LIVRAISON_EN_ATTENTE => 'En attente',
            self::STATUT_LIVRAISON_PARTIELLE  => 'Livraison partielle',
            self::STATUT_LIVRAISON_RECUE      => 'Reçue',
        ][$this->statut_livraison] ?? 'Indéfini';
    }

    public function getTypeAchatLabelAttribute(): string
    {
        return [
            self::TYPE_ACHAT_CANTINE  => 'Cantine',
            self::TYPE_ACHAT_BOUTIQUE => 'Boutique',
        ][$this->type_achat] ?? 'Indéfini';
    }
}
