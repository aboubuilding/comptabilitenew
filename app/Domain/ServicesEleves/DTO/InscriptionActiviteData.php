<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class InscriptionActiviteData
{
    public function __construct(
        public int    $eleveId,
        public int    $activiteId,
        public ?float $montantDu,
        public ?string $dateInscription,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            eleveId:         (int) $data['eleve_id'],
            activiteId:      (int) $data['activite_id'],
            montantDu:       isset($data['montant_du']) ? (float) $data['montant_du'] : null,
            dateInscription: $data['date_inscription'] ?? now()->toDateString(),
        );
    }
}