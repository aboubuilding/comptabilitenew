<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\TypePaiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle SEULEMENT — dépendance de référence pour FraisEcole. Le workflow
 * "Souscrire un frais pour une famille et appliquer une remise" relève
 * plutôt de l'écran financier d'un élève/inscription (pas de cet écran
 * de paramétrage des frais) — à construire avec ce module.
 */
class Souscription extends Model
{
    use HasFactory;

    protected $table = 'souscriptions';

    protected $fillable = [
        'date_souscription', 'montant_annuel_prevu', 'taux_remise', 'type_paiement',
        'frais_ecole_id', 'niveau_id', 'annee_id', 'inscription_id', 'utilisateur_id',
        'ligne_id', 'zone_id', 'etat',
    ];

    protected $casts = [
        'date_souscription'     => 'date',
        'montant_annuel_prevu'  => 'float',
        'taux_remise'           => 'float',
        'type_paiement'         => TypePaiement::class,
        'etat'                  => 'integer',
    ];

    public function fraisEcole()
    {
        return $this->belongsTo(FraisEcole::class);
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}