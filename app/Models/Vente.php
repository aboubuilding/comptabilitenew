<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ventes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'date_vente',
        'annee_id',
        'paiement_id',
        'utilisateur_id',
        'magasin_id',
        'unite',
        'prix_unitaire',
        'total_ht',
        'total_ttc',
        'type_vente',
        'statut_paiement',
        'client_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_vente'       => 'date',
        'prix_unitaire'    => 'decimal:2',
        'total_ht'         => 'decimal:2',
        'total_ttc'        => 'decimal:2',
        'type_vente'       => 'integer',
        'statut_paiement'  => 'integer',
        'etat'             => 'boolean',
    ];

    // ===== CONSTANTES =====
    // Type de vente
    const TYPE_VENTE_COMPTOIR    = 1;
    const TYPE_VENTE_ABONNEMENT  = 2;

    // Statut de paiement
    const STATUT_PAIEMENT_IMPAYE = 0;
    const STATUT_PAIEMENT_PAYE   = 1;
    const STATUT_PAIEMENT_ANNULE = 2;

    // État actif/inactif
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    /**
     * Relation avec le paiement.
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    /**
     * Relation avec l'utilisateur (vendeur).
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }

    /**
     * Relation avec le magasin.
     */
    public function magasin()
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    /**
     * Relation avec le client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si la vente est active.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Vérifier si le paiement est effectué.
     */
    public function isPaye(): bool
    {
        return $this->statut_paiement == self::STATUT_PAIEMENT_PAYE;
    }

    /**
     * Vérifier si c'est une vente au comptoir.
     */
    public function isVenteComptoir(): bool
    {
        return $this->type_vente == self::TYPE_VENTE_COMPTOIR;
    }

    /**
     * Libellé du type de vente.
     */
    public function getTypeVenteLabelAttribute(): string
    {
        return [
            self::TYPE_VENTE_COMPTOIR   => 'Vente au comptoir',
            self::TYPE_VENTE_ABONNEMENT => 'Abonnement',
        ][$this->type_vente] ?? 'Indéfini';
    }

    /**
     * Libellé du statut de paiement.
     */
    public function getStatutPaiementLabelAttribute(): string
    {
        return [
            self::STATUT_PAIEMENT_IMPAYE => 'Impayé',
            self::STATUT_PAIEMENT_PAYE   => 'Payé',
            self::STATUT_PAIEMENT_ANNULE => 'Annulé',
        ][$this->statut_paiement] ?? 'Inconnu';
    }
}
