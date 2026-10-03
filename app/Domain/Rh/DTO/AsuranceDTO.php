<?php

namespace App\Domain\Rh\DTO;

/**
 * DTO immuable pour la création/mise à jour d'une assurance du catalogue.
 */
final readonly class AsuranceDTO
{
    public function __construct(
        public string $nom_assureur,
        public string $police_assurance,
        public ?float $montant_annuel = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nom_assureur:     (string) $data['nom_assureur'],
            police_assurance: (string) $data['police_assurance'],
            montant_annuel:   isset($data['montant_annuel']) && $data['montant_annuel'] !== null
                                ? (float) $data['montant_annuel']
                                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nom_assureur'     => $this->nom_assureur,
            'police_assurance' => $this->police_assurance,
            'montant_annuel'   => $this->montant_annuel,
        ];
    }
}