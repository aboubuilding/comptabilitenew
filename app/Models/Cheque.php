<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cheques';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'numero',
        'emetteur',
        'annee_id',
        'paiement_id',
        'date_emission',
        'statut',
        'date_encaissement',
        'banque_id',
        'etat',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_emission'    => 'date',
        'date_encaissement'=> 'date',
        'etat'             => 'boolean',    // car integer avec 0/1
        'statut'           => 'integer',    // tinyInteger stocké en base
        'annee_id'         => 'integer',
        'paiement_id'      => 'integer',
        'banque_id'        => 'integer',
    ];

    // ===== CONSTANTES POUR LE CHAMP `statut` =====
    const STATUT_EN_ATTENTE  = 1;  // Exemple : chèque émis mais non encaissé
    const STATUT_ENCAISSE     = 2;
    const STATUT_REJETE       = 3;
    const STATUT_ANNULE       = 4;

    // ===== CONSTANTES POUR LE CHAMP `etat` =====
    const ETAT_ACTIF   = 1;
    const ETAT_INACTIF = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'année (si la table `annees` existe)
     */
    public function annee()
    {
        return $this->belongsTo(Annee::class, 'annee_id');
    }

    /**
     * Relation avec le paiement (table `paiements`)
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    /**
     * Relation avec la banque (table `banques`)
     */
    public function banque()
    {
        return $this->belongsTo(Banque::class, 'banque_id');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si le chèque est actif
     */
    public function isActif(): bool
    {
        return $this->etat == self::ETAT_ACTIF;
    }

    /**
     * Vérifier si le chèque a été encaissé
     */
    public function isEncaisse(): bool
    {
        return $this->statut == self::STATUT_ENCAISSE;
    }

    /**
     * Définir le statut à partir d'une chaîne (si besoin)
     */
    public function setStatutFromString(string $status): void
    {
        $map = [
            'en_attente' => self::STATUT_EN_ATTENTE,
            'encaisse'   => self::STATUT_ENCAISSE,
            'rejete'     => self::STATUT_REJETE,
            'annule'     => self::STATUT_ANNULE,
        ];

        $this->statut = $map[$status] ?? self::STATUT_EN_ATTENTE;
    }

}
