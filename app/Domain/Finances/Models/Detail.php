<?php

namespace App\Domain\Finances\Models;

use App\Domain\Logistique\Models\Produit;
use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Domain\ServicesEleves\Models\Activite;
use App\Domain\ServicesEleves\Models\Evenement;
use App\Domain\ServicesEleves\Models\InscriptionCantine;
use App\Domain\Finances\Types\StatutPaiement;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Réécrit avec les champs ajoutés par les migrations récentes
 * (produit_id, service_id, activite_id, evenement_id, quantite,
 * remise_percent, abonnement_bus_id, inscription_cantine_id). La
 * "nature" d'une ligne (cahier des charges §4, 2.2) se déduit de la
 * colonne FK renseignée — voir nature()/natureLabel().
 */
class Detail extends Model
{
    use HasFactory;

    protected $table = 'details';

    protected $fillable = [
        'montant', 'remise_percent', 'libelle', 'paiement_id', 'type_paiement',
        'inscription_id', 'frais_ecole_id', 'statut_paiement', 'annee_id', 'souscription_id',
        'caisse_id', 'comptable_id', 'caissier_id', 'date_paiement', 'date_encaissement',
        'motif_annulation', 'etat',
        'produit_id', 'service_id', 'activite_id', 'evenement_id', 'quantite',
        'abonnement_bus_id', 'inscription_cantine_id',
    ];

    protected $casts = [
        'montant'           => 'float',
        'remise_percent'    => 'float',
        'quantite'          => 'integer',
        'date_paiement'     => 'date',
        'date_encaissement' => 'date',
        'statut_paiement'   => StatutPaiement::class,
        'etat'              => 'integer',
    ];

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function activite()
    {
        return $this->belongsTo(Activite::class);
    }

    public function evenement()
    {
        return $this->belongsTo(Evenement::class);
    }

    public function abonnementBus()
    {
        return $this->belongsTo(AbonnementBus::class);
    }

    public function inscriptionCantine()
    {
        return $this->belongsTo(InscriptionCantine::class);
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class);
    }

    /**
     * Nature de la ligne, déduite de la FK renseignée — un seul type de
     * FK est censé être non nul à la fois. Ordre de préséance sans
     * incidence en usage normal, choisi pour être explicite en cas de
     * données incohérentes.
     */
    public function nature(): string
    {
        return match (true) {
            ! is_null($this->produit_id)             => 'produit',
            ! is_null($this->service_id)              => 'service',
            ! is_null($this->activite_id)              => 'activite',
            ! is_null($this->evenement_id)              => 'evenement',
            ! is_null($this->abonnement_bus_id)          => 'bus',
            ! is_null($this->inscription_cantine_id)      => 'cantine',
            ! is_null($this->frais_ecole_id)               => 'ecolage',
            default                                         => 'autre',
        };
    }

    public function natureLabel(): string
    {
        return match ($this->nature()) {
            'produit'   => 'Produit',
            'service'   => 'Service',
            'activite'  => 'Activité',
            'evenement' => 'Événement',
            'bus'       => 'Bus',
            'cantine'   => 'Cantine',
            'ecolage'   => 'Écolage',
            default     => 'Autre',
        };
    }

    public function scopeEncaisse($query)
    {
        return $query->whereNotNull('date_encaissement');
    }

    public function scopeActive($query)
    {
        return $query->where('etat', 1);
    }
}