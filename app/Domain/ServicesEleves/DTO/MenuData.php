<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class MenuData
{
    /**
     * @param MenuProduitData[] $produits
     */
    public function __construct(
        public string  $libelle,
        public ?string $description,
        public string  $dateService,
        public int     $typeRepas,
        public int     $quantitePrevue,
        public int     $magasinId,
        public array   $produits,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:        trim($data['libelle']),
            description:    $data['description'] ?? null,
            dateService:    $data['date_service'],
            typeRepas:      (int) $data['type_repas'],
            quantitePrevue: (int) $data['quantite_prevue'],
            magasinId:      (int) $data['magasin_id'],
            produits:       array_map(
                fn (array $row) => MenuProduitData::fromArray($row),
                $data['produits'] ?? []
            ),
        );
    }
}
