<?php

namespace App\Domain\Finances\DTO;

final readonly class ClotureCaisseData
{
    public function __construct(
        public int     $caisseId,
        public float   $soldeCompte,
        public ?string $motifEcart,
        public int     $utilisateurId,
    ) {}

    public static function fromRequest(int $caisseId, array $data, int $userId): self
    {
        return new self(
            caisseId:      $caisseId,
            soldeCompte:   (float) $data['solde_compte'],
            motifEcart:    $data['motif_ecart'] ?? null,
            utilisateurId: $userId,
        );
    }
}