<?php

namespace App\Domain\Logistique\Models;

use App\Domain\Logistique\Types\AchatStatutLivraison;
use App\Domain\Logistique\Types\AchatStatutPaiement;
use App\Domain\Logistique\Types\AchatType;
use App\Domain\Administration\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achat extends Model
{
    protected $table = 'achats';

    protected $fillable = [
        'date_achat', 'nom_acheteur', 'reference', 'bon_commande', 'commentaire',
        'fournisseur_id', 'annee_id',
        'statut_paiement', 'statut_livraison',
        'montant_total', 'type_achat', 'etat',
    ];

    protected $casts = [
        'date_achat'       => 'date',
        'statut_paiement'  => AchatStatutPaiement::class,
        'statut_livraison' => AchatStatutLivraison::class,
        'type_achat'       => AchatType::class,
        'montant_total'    => 'float',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailAchat::class, 'achat_id');
    }

    public function isLivre(): bool
    {
        return $this->statut_livraison === AchatStatutLivraison::LIVRE;
    }

    public function isAnnule(): bool
    {
        return $this->statut_livraison === AchatStatutLivraison::ANNULE;
    }

    /** Recalcule et met à jour `montant_total` à partir des lignes. */
    public function recalculerMontantTotal(): void
    {
        $total = round((float) $this->details()->where('etat', 1)->sum('montant_achat'), 2);
        $this->update(['montant_total' => $total]);
    }
}