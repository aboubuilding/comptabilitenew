<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mouvements_stock';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date_stock',
        'produit_id',
        'magasin_id',
        'magasin_dest_id',
        'bon_id',
        'annee_id',
        'quantite',
        'type_mouvement',
        'reference',
        'utilisateur_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_stock'     => 'date',
        'quantite'       => 'float',
        'type_mouvement' => 'integer',
        'etat'           => 'boolean',
    ];

    // ===== CONSTANTES POUR `type_mouvement` =====
    const MOUVEMENT_ENTREE    = 1;
    const MOUVEMENT_SORTIE    = 2;
    const MOUVEMENT_TRANSFERT = 3;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec le produit.
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /**
     * Relation avec le magasin source.
     */
    public function magasin()
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    /**
     * Relation avec le magasin destination (pour les transferts).
     */
    public function magasinDest()
    {
        return $this->belongsTo(Magasin::class, 'magasin_dest_id');
    }

    /**
     * Relation avec le bon (document associé).
     */
    public function bon()
    {
        return $this->belongsTo(Bon::class, 'bon_id');
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    /**
     * Relation avec l'utilisateur qui a effectué le mouvement.
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    public function isEntree(): bool
    {
        return $this->type_mouvement == self::MOUVEMENT_ENTREE;
    }

    public function isSortie(): bool
    {
        return $this->type_mouvement == self::MOUVEMENT_SORTIE;
    }

    public function isTransfert(): bool
    {
        return $this->type_mouvement == self::MOUVEMENT_TRANSFERT;
    }

    public function getTypeMouvementLabelAttribute(): string
    {
        return [
            self::MOUVEMENT_ENTREE    => 'Entrée',
            self::MOUVEMENT_SORTIE    => 'Sortie',
            self::MOUVEMENT_TRANSFERT => 'Transfert',
        ][$this->type_mouvement] ?? 'Indéfini';
    }

    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }
}
