<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbonnementBus extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'abonnements_bus';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inscription_id',
        'date_debut',
        'date_fin',
        'montant_mensuel',
        'nombre_mois',
        'montant_total_du',
        'statut',
        'date_abandon',
        'motif_abandon',
        'abandonne_par',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'montant_mensuel'  => 'decimal:2',
        'montant_total_du' => 'decimal:2',
        'statut'           => 'integer',
        'date_abandon'     => 'date',
    ];

    // ===== CONSTANTES POUR `statut` =====
    const STATUT_ACTIF     = 1;
    const STATUT_ABANDONNE = 0;

    // ===== RELATIONS =====
    /**
     * Relation avec l'inscription (élève).
     */
    public function inscription()
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    /**
     * Relation avec l'utilisateur qui a enregistré l'abandon.
     */
    public function abandonnePar()
    {
        return $this->belongsTo(User::class, 'abandonne_par');
    }

    // ===== MÉTHODES UTILITAIRES =====
    /**
     * Vérifier si l'abonnement est actif.
     */
    public function isActif(): bool
    {
        return $this->statut == self::STATUT_ACTIF;
    }

    /**
     * Vérifier si l'abonnement est abandonné.
     */
    public function isAbandonne(): bool
    {
        return $this->statut == self::STATUT_ABANDONNE;
    }

    /**
     * Calculer automatiquement le montant total dû.
     */
    public function calculerTotalDu(): void
    {
        if ($this->nombre_mois && $this->montant_mensuel) {
            $this->montant_total_du = $this->nombre_mois * $this->montant_mensuel;
        }
    }

    /**
     * Abandonner l'abonnement.
     *
     * @param int $userId
     * @param string|null $motif
     * @return void
     */
    public function abandonner(int $userId, ?string $motif = null): void
    {
        $this->statut = self::STATUT_ABANDONNE;
        $this->date_abandon = now();
        $this->motif_abandon = $motif;
        $this->abandonne_par = $userId;
        $this->save();
    }
}
