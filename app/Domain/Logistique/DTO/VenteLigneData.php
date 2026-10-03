<?php

namespace App\Domain\Logistique\DTO;

final readonly class VenteLigneData
{
    public function __construct(
        public int    $produitId,
        public float  $quantite,
        public float  $prixUnitaire,
        public float  $remise      = 0,       // en %
        public ?int   $inscriptionId = null,  // si l'acheteur est un élève
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            produitId:     (int) $row['produit_id'],
            quantite:      (float) $row['quantite'],
            prixUnitaire:  (float) $row['prix_unitaire'],
            remise:        (float) ($row['remise'] ?? 0),
            inscriptionId: isset($row['inscription_id']) ? (int) $row['inscription_id'] : null,
        );
    }

    public function montantLigne(): float
    {
        return round($this->quantite * $this->prixUnitaire * (1 - $this->remise / 100), 2);
    }
}