<?php

namespace App\Domain\Finances\DTO;

use App\Domain\Finances\Types\TypeDetail;

final readonly class PanierLigneData
{
    public function __construct(
        public TypeDetail $type,
        public string     $libelle,
        public float      $montant,
        public ?float     $quantite       = null,
        public ?float     $prixUnitaire   = null,
        public ?int       $fraisEcoleId   = null,
        public ?int       $souscriptionId = null,
        public ?int       $produitId      = null,
        public ?int       $serviceId      = null,
        public ?int       $activiteId     = null,
        public ?int       $evenementId    = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            type:         TypeDetail::from((int) $row['type_paiement']),
            libelle:      $row['libelle'],
            montant:      (float) $row['montant'],
            quantite:     isset($row['quantite']) ? (float) $row['quantite'] : null,
            prixUnitaire: isset($row['prix_unitaire']) ? (float) $row['prix_unitaire'] : null,
            fraisEcoleId: isset($row['frais_ecole_id']) ? (int) $row['frais_ecole_id'] : null,
            souscriptionId: isset($row['souscription_id']) ? (int) $row['souscription_id'] : null,
            produitId:    isset($row['produit_id']) ? (int) $row['produit_id'] : null,
            serviceId:    isset($row['service_id']) ? (int) $row['service_id'] : null,
            activiteId:   isset($row['activite_id']) ? (int) $row['activite_id'] : null,
            evenementId:  isset($row['evenement_id']) ? (int) $row['evenement_id'] : null,
        );
    }

    public function toDetailPayload(int $paiementId, int $inscriptionId, int $anneeId, int $comptableId): array
    {
        return [
            'paiement_id'    => $paiementId,
            'inscription_id' => $inscriptionId,
            'type_paiement'  => $this->type->value,
            'libelle'        => $this->libelle,
            'montant'        => $this->montant,
            'quantite'       => $this->quantite,
            'prix_unitaire'  => $this->prixUnitaire,
            'frais_ecole_id' => $this->fraisEcoleId,
            'souscription_id'=> $this->souscriptionId,
            'produit_id'     => $this->produitId,
            'service_id'     => $this->serviceId,
            'activite_id'    => $this->activiteId,
            'evenement_id'   => $this->evenementId,
            'statut_paiement'=> \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value,
            'comptable_id'   => $comptableId,
            'date_paiement'  => now()->toDateString(),
            'annee_id'       => $anneeId,
        ];
    }
}