<?php

namespace App\Domain\Finances\Models;

use App\Domain\Administration\Models\User;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Engagement d'un élève sur un service : cantine, transport, activité
 * extrascolaire, événement (ou, depuis l'écran Souscriptions, un frais
 * assorti d'une remise).
 *
 * `origine` : « reprise » (base historique), « saisie » (écran), ou le
 * module qui porte l'abonnement (cantine, bus, activite, evenement).
 * `montant_paye` est renseigné par SouscriptionRepository (lignes encaissées).
 */
class Souscription extends Model
{
    protected $table = 'souscriptions';

    protected $fillable = [
        'date_souscription', 'montant_annuel_prevu', 'taux_remise',
        'type_paiement', 'origine', 'origine_id', 'libelle',
        'frais_ecole_id', 'activite_id', 'evenement_id', 'niveau_id', 'annee_id',
        'inscription_id', 'utilisateur_id', 'etat',
    ];

    protected $casts = [
        'date_souscription'    => 'date',
        'montant_annuel_prevu' => 'float',
        'taux_remise'          => 'float',
        'type_paiement'        => TypeDetail::class,
    ];

    public function fraisEcole(): BelongsTo
    {
        return $this->belongsTo(FraisEcole::class, 'frais_ecole_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'inscription_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /** Nom affiché : libellé repris, sinon le frais, sinon la nature. */
    public function getIntituleAttribute(): string
    {
        return $this->libelle ?: ($this->fraisEcole?->libelle ?: ($this->type_paiement?->label() ?? '—'));
    }

    /** Montant après application de la remise. */
    public function getMontantNetAttribute(): float
    {
        return round(
            (float) $this->montant_annuel_prevu * (1 - (float) $this->taux_remise / 100),
            2
        );
    }

    /** Montant total de la remise accordée. */
    public function getMontantRemiseAttribute(): float
    {
        return round(
            (float) $this->montant_annuel_prevu * ((float) $this->taux_remise / 100),
            2
        );
    }

    /** Ce qui reste à percevoir (jamais négatif). */
    public function getResteAttribute(): float
    {
        return max(0.0, round($this->montant_net - (float) ($this->attributes['montant_paye'] ?? 0), 2));
    }

    /** « solde » (tout est encaissé), « partiel » ou « impaye ». */
    public function getEtatPaiementAttribute(): string
    {
        $paye = (float) ($this->attributes['montant_paye'] ?? 0);

        return match (true) {
            $paye + 0.005 >= $this->montant_net => 'solde',
            $paye > 0                           => 'partiel',
            default                             => 'impaye',
        };
    }
}
