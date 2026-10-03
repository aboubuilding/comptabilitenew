<?php

namespace App\Domain\Finances\Models;

use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detail extends Model
{
    protected $table = 'details';

    protected $fillable = [
        'montant', 'quantite', 'prix_unitaire', 'libelle',
        'paiement_id', 'type_paiement',
        'inscription_id', 'frais_ecole_id', 'souscription_id',
        'produit_id', 'service_id', 'activite_id', 'evenement_id',
        'statut_paiement', 'annee_id',
        'caisse_id', 'comptable_id', 'caissier_id',
        'date_paiement', 'date_encaissement', 'etat',
    ];

    protected $casts = [
        'type_paiement'   => TypeDetail::class,
        'statut_paiement' => PaiementStatut::class,
        'montant'         => 'float',
        'quantite'        => 'float',
        'prix_unitaire'   => 'float',
        'date_paiement'   => 'date',
        'date_encaissement' => 'date',
    ];

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }

    public function comptable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comptable_id');
    }

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function isEncaisse(): bool
    {
        return $this->statut_paiement === PaiementStatut::ENCAISSE;
    }
}