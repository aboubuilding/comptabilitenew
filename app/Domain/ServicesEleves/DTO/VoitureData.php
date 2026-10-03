<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class VoitureData
{
    public function __construct(
        public string  $marque,
        public ?string $modele,
        public string  $plaque,
        public ?int    $nombrePlace,
        public ?int    $anneeFabrication,
        public ?string $numeroChassis,
        public ?string $dateAchat,
        public ?float  $prixAchat,
        public ?string $fournisseur,
        public ?float  $kilometrageActuel,
        public int     $statut,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            marque:           trim($data['marque']),
            modele:           $data['modele'] ?? null,
            plaque:           strtoupper(trim($data['plaque'])),
            nombrePlace:      isset($data['nombre_place']) ? (int) $data['nombre_place'] : null,
            anneeFabrication: isset($data['annee_fabrication']) ? (int) $data['annee_fabrication'] : null,
            numeroChassis:    $data['numero_chassis'] ?? null,
            dateAchat:        $data['date_achat'] ?? null,
            prixAchat:        isset($data['prix_achat']) ? (float) $data['prix_achat'] : null,
            fournisseur:      $data['fournisseur'] ?? null,
            kilometrageActuel: isset($data['kilometrage_actuel']) ? (float) $data['kilometrage_actuel'] : 0,
            statut:           (int) ($data['statut'] ?? 1),
        );
    }
}