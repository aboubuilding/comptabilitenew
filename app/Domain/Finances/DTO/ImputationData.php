<?php

namespace App\Domain\Finances\DTO;

final readonly class ImputationData
{
    public function __construct(
        public int     $centreId,
        public ?float  $montant,
        public ?float  $pourcentage,
        public string  $sens,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            centreId:    (int) $row['centre_analytique_id'],
            montant:     isset($row['montant']) && $row['montant'] !== ''
                ? (float) $row['montant'] : null,
            pourcentage: isset($row['pourcentage']) && $row['pourcentage'] !== ''
                ? (float) $row['pourcentage'] : null,
            sens:        $row['sens'] ?? 'depense',
        );
    }
}