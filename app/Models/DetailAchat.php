<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailAchat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'detail_achats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'achat_id',
        'produit_id',
        'annee_id',
        'quantite',
        'prix_unitaire',
        'montant_achat',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantite'       => 'float',
        'prix_unitaire'  => 'float',
        'montant_achat'  => 'float',
        'etat'           => 'boolean',
    ];

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'achat parent.
     */
    public function achat()
    {
        return $this->belongsTo(Achat::class, 'achat_id');
    }

    /**
     * Relation avec le produit.
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le détail est actif.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Calculer automatiquement le montant_achat si besoin.
     */
    public function calculerMontant(): void
    {
        $this->montant_achat = $this->quantite * $this->prix_unitaire;
    }
}
