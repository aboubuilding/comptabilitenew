<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Magasin extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'magasins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'libelle',
        'responsable',
        'description',
        'adresse',
        'telephone',
        'type',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'integer',   // tinyInteger
        'etat' => 'boolean',   // 0/1
    ];

    // ===== CONSTANTES POUR `type` =====
    const TYPE_ENTREPOT = 1;
    const TYPE_BOUTIQUE = 2;

    // ===== CONSTANTES POUR `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le magasin est actif.
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer le magasin.
     */
    public function activer(): void
    {
        $this->etat = self::ETAT_ACTIF;
        $this->save();
    }

    /**
     * Désactiver le magasin.
     */
    public function desactiver(): void
    {
        $this->etat = self::ETAT_INACTIF;
        $this->save();
    }

    /**
     * Vérifier si c'est un entrepôt.
     */
    public function isEntrepot(): bool
    {
        return $this->type == self::TYPE_ENTREPOT;
    }

    /**
     * Vérifier si c'est une boutique.
     */
    public function isBoutique(): bool
    {
        return $this->type == self::TYPE_BOUTIQUE;
    }

    /**
     * Libellé du type de magasin.
     */
    public function getTypeLabelAttribute(): string
    {
        return [
            self::TYPE_ENTREPOT => 'Entrepôt',
            self::TYPE_BOUTIQUE => 'Boutique',
        ][$this->type] ?? 'Indéfini';
    }
}
