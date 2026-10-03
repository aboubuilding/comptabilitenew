<?php

namespace App\Domain\Logistique\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Fournisseur extends Model
{
    protected $table = 'fournisseurs';

    protected $fillable = [
        'raison_social', 'nom_contact', 'telephone_contact', 'email_contact',
        'adresse', 'nif', 'rccm', 'etat',
    ];

    /**
     * ACHATS — relation vers le module 3.2.
     * Tolérante : si la table `achats` n'existe pas encore, les agrégats
     * renvoient 0 sans casser la page.
     */
    public function achats(): HasMany
    {
        return $this->hasMany(Achat::class, 'fournisseur_id');
    }

    /* ================================================================
       AGRÉGATS — tolérants à l'absence de la table achats
       ================================================================ */

    public function getNombreAchatsAttribute(): int
    {
        if (! Schema::hasTable('achats')) {
            return 0;
        }

        return $this->achats()->where('etat', 1)->count();
    }

    public function getMontantTotalAchatsAttribute(): float
    {
        if (! Schema::hasTable('achats')) {
            return 0.0;
        }

        return round(
            (float) $this->achats()->where('etat', 1)->sum('montant_total'),
            2
        );
    }

    /** Dernier achat — utile pour afficher une date de dernière commande. */
    public function getDateDernierAchatAttribute(): ?\Carbon\Carbon
    {
        if (! Schema::hasTable('achats')) {
            return null;
        }

        $dernier = $this->achats()
            ->where('etat', 1)
            ->orderByDesc('date_achat')
            ->first(['date_achat']);

        return $dernier?->date_achat;
    }

    public function isActif(): bool
    {
        return (int) $this->etat === 1;
    }
}