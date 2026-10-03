<?php

namespace App\Domain\Finances\DTO;

final readonly class PlanEcheancierData
{
    /**
     * @param array<int, array{ordre:int, libelle:string, jour_echeance?:int, date_echeance?:string, montant?:float, pourcentage?:float}> $lignes
     */
    public function __construct(
        public string  $nom,
        public ?string $description,
        public array   $lignes = [],
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nom:         trim($data['nom']),
            description: $data['description'] ?? null,
            lignes:      $data['lignes'] ?? [],
        );
    }
}