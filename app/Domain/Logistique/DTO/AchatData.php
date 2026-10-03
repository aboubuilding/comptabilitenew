<?php

namespace App\Domain\Logistique\DTO;

final readonly class AchatData
{
    /**
     * @param AchatLigneData[] $lignes
     */
    public function __construct(
        public string  $dateAchat,
        public ?string $nomAcheteur,
        public ?string $bonCommande,
        public ?string $commentaire,
        public int     $fournisseurId,
        public int     $typeAchat,
        public array   $lignes,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            dateAchat:     $data['date_achat'],
            nomAcheteur:   $data['nom_acheteur'] ?? null,
            bonCommande:   $data['bon_commande'] ?? null,
            commentaire:   $data['commentaire'] ?? null,
            fournisseurId: (int) $data['fournisseur_id'],
            typeAchat:     (int) ($data['type_achat'] ?? 1),
            lignes:        array_map(
                fn (array $l) => AchatLigneData::fromArray($l),
                $data['lignes']
            ),
        );
    }

    public function montantTotal(): float
    {
        return round(array_sum(array_map(fn (AchatLigneData $l) => $l->montant(), $this->lignes)), 2);
    }
}