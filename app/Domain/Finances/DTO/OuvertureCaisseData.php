<?php

namespace App\Domain\Finances\DTO;

final readonly class OuvertureCaisseData
{
    public function __construct(
        public string $libelle,
        public int    $caissierId,
        public float  $soldeInitial,
        public int    $auteurId,
    ) {}

    public static function fromRequest(array $data, int $auteurId): self
    {
        return new self(
            libelle:      $data['libelle'],
            caissierId:   (int) $data['responsable_id'],
            soldeInitial: (float) $data['solde_initial'],
            auteurId:     $auteurId,
        );
    }
}