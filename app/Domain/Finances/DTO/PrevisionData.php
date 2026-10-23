<?php

namespace App\Domain\Finances\DTO;

/**
 * Ligne budgétaire : un libellé, un montant global et, facultatif, le centre
 * analytique dont elle forme le budget (l'année est celle de l'en-tête).
 */
final readonly class PrevisionData
{
    public function __construct(
        public string $libelle,
        public float  $montantPrevisionnel,
        public int    $auteurId,
        public ?int   $centreAnalytiqueId = null,
    ) {}

    public static function fromRequest(array $data, int $auteurId): self
    {
        return new self(
            libelle:             trim($data['libelle']),
            montantPrevisionnel: (float) $data['montant_previsionnel'],
            auteurId:            $auteurId,
            centreAnalytiqueId:  ! empty($data['centre_analytique_id']) ? (int) $data['centre_analytique_id'] : null,
        );
    }
}
