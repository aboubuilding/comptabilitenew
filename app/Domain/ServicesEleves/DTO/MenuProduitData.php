<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class MenuProduitData
{
    public function __construct(
        public int    $produitId,
        public float  $quantite,
        public float  $coutUnitaire,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            produitId:    (int) $row['produit_id'],
            quantite:     (float) $row['quantite'],
            coutUnitaire: (float) ($row['cout_unitaire'] ?? 0),
        );
    }

    public function coutTotal(): float
    {
        return round($this->quantite * $this->coutUnitaire, 2);
    }
}