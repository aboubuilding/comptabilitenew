<?php

namespace App\Domain\Finances\DTO;

final readonly class PrevisionData
{
    public function __construct(
        public string  $libelle,
        public string  $type,
        public float   $montantPrevisionnel,
        public ?int    $centreAnalytiqueId,
        public ?string $dateDebut,
        public ?string $dateFin,
        public int     $auteurId,
    ) {}

    public static function fromRequest(array $data, int $auteurId): self
    {
        return new self(
            libelle:             trim($data['libelle']),
            type:                $data['type'],
            montantPrevisionnel: (float) $data['montant_previsionnel'],
            centreAnalytiqueId:  isset($data['centre_analytique_id']) ? (int) $data['centre_analytique_id'] : null,
            dateDebut:           $data['date_debut'] ?? null,
            dateFin:             $data['date_fin'] ?? null,
            auteurId:            $auteurId,
        );
    }
}