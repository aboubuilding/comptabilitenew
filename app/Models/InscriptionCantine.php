<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscriptionCantine extends Model
{
    protected $table = 'inscriptions_cantine';

    protected $fillable = [
        'inscription_id', 'frais_ecole_id', 'date_debut', 'date_fin',
        'montant_mensuel', 'nombre_mois', 'montant_total_du',
        'statut', 'date_abandon', 'motif_abandon', 'abandonne_par'
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

    public function fraisEcole()
    {
        return $this->belongsTo(FraisEcole::class);
    }

    public function detailsPaiement()
    {
        return $this->hasMany(DetailPaiement::class, 'inscription_cantine_id');
    }

    public function getMontantPayeAttribute(): float
    {
        return $this->detailsPaiement()
            ->where('statut_paiement', 1)
            ->sum('montant');
    }

    public function getMontantResteAttribute(): float
    {
        return $this->montant_total_du - $this->montant_paye;
    }
}