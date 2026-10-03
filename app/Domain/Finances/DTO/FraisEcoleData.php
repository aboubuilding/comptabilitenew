<?php

namespace App\Domain\Finances\DTO;

final readonly class FraisEcoleData
{
    public function __construct(
        public string  $libelle,
        public float   $montant,
        public int     $typeDetail,
        public int     $modalitePaiement,
        public int     $typeForfait,
        public ?int    $niveauId,
        public ?int    $planEcheancierId,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:          trim($data['libelle']),
            montant:          (float) $data['montant'],
            typeDetail:       (int) $data['type_paiement'],
            modalitePaiement: (int) $data['modalite_paiement'],
            typeForfait:      (int) $data['type_forfait'],
            niveauId:         isset($data['niveau_id']) ? (int) $data['niveau_id'] : null,
            planEcheancierId: isset($data['plan_echeancier_id']) ? (int) $data['plan_echeancier_id'] : null,
        );
    }
}