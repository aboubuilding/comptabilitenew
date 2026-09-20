<?php

namespace App\Domain\Finances\DTO;

use App\Domain\Finances\Types\MouvementType;

final readonly class MouvementCaisseData
{
    public function __construct(
        public int           $caisseId,
        public MouvementType $type,
        public string        $libelle,
        public float         $montant,
        public string        $dateMouvement,
        public int           $utilisateurId,
        public ?string       $beneficiaire = null,
        public ?string       $motif        = null,
    ) {}

    public static function fromRequest(int $caisseId, array $data, int $userId): self
    {
        return new self(
            caisseId:      $caisseId,
            type:          MouvementType::from((int) $data['type_mouvement']),
            libelle:       $data['libelle'],
            montant:       (float) $data['montant'],
            dateMouvement: $data['date_mouvement'],
            utilisateurId: $userId,
            beneficiaire:  $data['beneficiaire'] ?? null,
            motif:         $data['motif'] ?? null,
        );
    }
}