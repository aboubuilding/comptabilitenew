<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbonnementBus extends Model
{
    protected $table = 'abonnements_bus';

    protected $fillable = [
        'inscription_id', 'date_debut', 'date_fin', 'montant_mensuel',
        'nombre_mois', 'montant_total_du', 'statut', 'date_abandon',
        'motif_abandon', 'abandonne_par'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_abandon' => 'date',
        'montant_mensuel' => 'decimal:2',
        'montant_total_du' => 'decimal:2',
        'statut' => 'integer',
    ];

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function eleve()
    {
        return $this->hasOneThrough(Eleve::class, Inscription::class, 'id', 'id', 'inscription_id', 'eleve_id');
    }

    public function detailsPaiement()
    {
        return $this->hasMany(DetailPaiement::class, 'abonnement_bus_id');
    }

    /**
     * Montant déjà payé pour cet abonnement
     */
    public function getMontantPayeAttribute(): float
    {
        return $this->detailsPaiement()
            ->where('statut_paiement', 1)
            ->sum('montant');
    }

    /**
     * Montant restant à payer
     */
    public function getMontantResteAttribute(): float
    {
        return $this->montant_total_du - $this->montant_paye;
    }
}