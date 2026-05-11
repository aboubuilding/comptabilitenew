<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentEleve extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parent_eleves';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom_parent',
        'prenom_parent',
        'telephone',
        'profession',
        'espace_id',
        'is_principal',
        'role',
        'annee_id',
        'nationalite_id',
        'whatsapp',
        'quartier',
        'adresse',
        'email',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'espace_id'      => 'integer',
        'is_principal'   => 'boolean',   // tinyInteger -> booléen
        'role'           => 'integer',   // tinyInteger
        'annee_id'       => 'integer',
        'nationalite_id' => 'integer',
        'etat'           => 'boolean',   // 0/1
    ];

    // ===== CONSTANTES POUR LE CHAMP `role` =====
    const ROLE_PERE      = 1;
    const ROLE_MERE      = 2;
    const ROLE_TUTEUR    = 3;
    const ROLE_AUTRE     = 4;

    // ===== CONSTANTES POUR LE CHAMP `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'espace (table `espaces`)
     */
    public function espace()
    {
        return $this->belongsTo(Espace::class, 'espace_id');
    }

    /**
     * Relation avec l'année (table `annees`)
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    /**
     * Relation avec la nationalité (table `nationalites`)
     */
    public function nationalite()
    {
        return $this->belongsTo(Nationalite::class, 'nationalite_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le parent est principal
     */
    public function isPrincipal(): bool
    {
        return (bool) $this->is_principal;
    }

    /**
     * Vérifier si le parent est actif
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Activer / désactiver le parent
     */
    public function setActif(bool $actif): void
    {
        $this->etat = $actif ? self::ETAT_ACTIF : self::ETAT_INACTIF;
        $this->save();
    }

    /**
     * Obtenir le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom_parent . ' ' . $this->nom_parent);
    }

    /**
     * Obtenir le libellé du rôle
     */
    public function getRoleLabelAttribute(): string
    {
        $labels = [
            self::ROLE_PERE   => 'Père',
            self::ROLE_MERE   => 'Mère',
            self::ROLE_TUTEUR => 'Tuteur',
            self::ROLE_AUTRE  => 'Autre',
        ];

        return $labels[$this->role] ?? 'Non défini';
    }
}
