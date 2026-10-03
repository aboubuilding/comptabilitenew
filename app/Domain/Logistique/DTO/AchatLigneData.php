<?php

namespace App\Domain\Logistique\DTO;

final readonly class AchatLigneData
{
    public function __construct(
        public int   $produitId,
        public float $quantite,
        public float $prixUnitaire,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            produitId:    (int) $row['produit_id'],
            quantite:     (float) $row['quantite'],
            prixUnitaire: (float) $row['prix_unitaire'],
        );
    }

    public function montant(): float
    {
        return round($this->quantite * $this->prixUnitaire, 2);
    }
}