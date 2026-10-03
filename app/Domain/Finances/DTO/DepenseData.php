<?php

namespace App\Domain\Finances\DTO;

final readonly class DepenseData
{
    public function __construct(
        public string  $libelle,
        public string  $beneficiaire,
        public ?string $motif,
        public string  $dateDepense,
        public float   $montant,
        public int     $auteurId,
    ) {}

    public static function fromRequest(array $data, int $auteurId): self
    {
        return new self(
            libelle:     $data['libelle'],
            beneficiaire: $data['beneficiaire'],
            motif:       $data['motif_depense'] ?? null,
            dateDepense: $data['date_depense'],
            montant:     (float) $data['montant'],
            auteurId:    $auteurId,
        );
    }
}