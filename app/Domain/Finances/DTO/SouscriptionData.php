<?php

namespace App\Domain\Finances\DTO;

final readonly class SouscriptionData
{
    public function __construct(
        public int    $fraisEcoleId,
        public int    $inscriptionId,
        public float  $tauxRemise,
        public string $dateSouscription,
        public ?int   $utilisateurId,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            fraisEcoleId:      (int) $data['frais_ecole_id'],
            inscriptionId:     (int) $data['inscription_id'],
            tauxRemise:        (float) ($data['taux_remise'] ?? 0),
            dateSouscription:  $data['date_souscription'] ?? now()->toDateString(),
            utilisateurId:     $userId,
        );
    }
}