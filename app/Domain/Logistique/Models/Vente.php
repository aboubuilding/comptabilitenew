<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\TypeVente;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vente extends Model
{
    protected $table = 'ventes';

    protected $fillable = [
        'reference', 'date_vente',
        'total_ht', 'total_ttc',
        'type_vente', 'statut_paiement',
        'client_id', 'magasin_id',
        'inscription_id', 'paiement_id',
        'utilisateur_id', 'annee_id', 'etat',
    ];

    protected $casts = [
        'date_vente'      => 'date',
        'total_ht'        => 'float',
        'total_ttc'       => 'float',
        'type_vente'      => TypeVente::class,
        'statut_paiement' => VenteStatutPaiement::class,
    ];

    public function details(): HasMany
    {
        return $this->hasMany(VenteDetail::class, 'vente_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function isPayee(): bool
    {
        return $this->statut_paiement === VenteStatutPaiement::PAYE;
    }

    public function isAnnulee(): bool
    {
        return $this->statut_paiement === VenteStatutPaiement::ANNULE;
    }

    /**
     * Nom de l'acheteur — élève si `inscription_id`, sinon client externe.
     */
    public function getAcheteurNomAttribute(): string
    {
        if ($this->inscription_id && $this->inscription) {
            return trim(
                ($this->inscription->eleve?->nom ?? '') . ' ' .
                ($this->inscription->eleve?->prenom ?? '')
            ) ?: 'Élève #' . $this->inscription_id;
        }

        if ($this->client_id && $this->client) {
            return $this->client->nom_raison_sociale;
        }

        return 'Client anonyme';
    }
}