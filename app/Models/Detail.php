<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Detail extends Model
{
    protected $table = 'details';
    protected $fillable = [
        'montant', 'libelle', 'paiement_id', 'type_paiement', 'inscription_id',
        'frais_ecole_id', 'produit_id', 'service_id', 'activite_id',
        'abonnement_bus_id', 'inscription_cantine_id', 'statut_paiement',
        'annee_id', 'souscription_id', 'caisse_id', 'comptable_id', 'caissier_id',
        'date_paiement', 'date_encaissement', 'etat'
    ];

    protected $casts = [
        'montant' => 'float',
        'type_paiement' => 'integer',
        'statut_paiement' => 'integer',
        'date_paiement' => 'date',
        'date_encaissement' => 'date',
        'etat' => 'integer',
    ];

    // ─────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────
    public function paiement() { return $this->belongsTo(Paiement::class); }
    public function inscription() { return $this->belongsTo(Inscription::class); }
    public function fraisEcole() { return $this->belongsTo(FraisEcole::class); }
    public function produit() { return $this->belongsTo(Produit::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function activite() { return $this->belongsTo(Activite::class); }
    public function abonnementBus() { return $this->belongsTo(AbonnementBus::class, 'abonnement_bus_id'); }
    public function inscriptionCantine() { return $this->belongsTo(InscriptionCantine::class, 'inscription_cantine_id'); }
    public function annee() { return $this->belongsTo(Annee::class); }
    public function caisse() { return $this->belongsTo(Caisse::class); }
    public function comptable() { return $this->belongsTo(User::class, 'comptable_id'); }
    public function caissier() { return $this->belongsTo(User::class, 'caissier_id'); }

    // ─────────────────────────────────────────────────────────
    // Accesseurs
    // ─────────────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_paiement) {
            1  => 'Frais inscription',
            2  => 'Scolarité',
            3  => 'Services',
            4  => 'Produit',
            5  => 'Frais location livre',
            6  => 'Caution',
            7  => 'Bus',
            8  => 'Cantine',
            9  => 'Autre',
            10 => 'Frais assurance',
            11 => 'Activité extra scolaire',
            12 => 'Frais examen',
            default => 'Inconnu',
        };
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut_paiement) {
            1 => 'En attente',
            2 => 'Encaissé',
            3 => 'En attente annulation',
            4 => 'Annulé',
            default => 'Inconnu',
        };
    }
}
