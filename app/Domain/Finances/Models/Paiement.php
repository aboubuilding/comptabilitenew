<?php

namespace App\Domain\Finances\Models;

use App\Domain\Administration\Models\User;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\StatutPaiement;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * L'en-tête d'un paiement (une opération = un Paiement + plusieurs
 * Detail, un par ligne/nature). 'inscription_id' ici sert de référence
 * principale d'affichage (l'écolage de l'élève concerné), pas de lien
 * exclusif : la ventilation réelle par nature vit dans les Detail liés.
 */
class Paiement extends Model
{
    use HasFactory;

    protected $table = 'paiements';

    protected $fillable = [
        'reference', 'payeur', 'motif_suppression', 'telephone_payeur', 'date_paiement',
        'statut_paiement', 'mode_paiement', 'inscription_id', 'utilisateur_id',
        'cheque_id', 'annee_id', 'montant', 'etat',
    ];

    protected $casts = [
        'date_paiement'    => 'date',
        'statut_paiement'  => StatutPaiement::class,
        'mode_paiement'    => ModePaiement::class,
        'montant'          => 'float',
        'etat'             => 'integer',
    ];

    public function details()
    {
        return $this->hasMany(Detail::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function isEncaisse(): bool
    {
        return $this->statut_paiement === StatutPaiement::Encaisse;
    }

    public function isAnnule(): bool
    {
        return ! empty($this->motif_suppression);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}