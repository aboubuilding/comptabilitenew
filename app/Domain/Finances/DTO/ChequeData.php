<?php

namespace App\Domain\Finances\DTO;

use App\Domain\Finances\Types\ChequeStatut;

final readonly class ChequeData
{
    public function __construct(
        public string       $numero,
        public string       $emetteur,
        public float        $montant,
        public int          $banqueId,
        public string       $dateEmission,
        public ChequeStatut $statut = ChequeStatut::EMIS,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            numero:       $data['numero'],
            emetteur:     $data['emetteur'],
            montant:      (float) $data['montant'],
            banqueId:     (int) $data['banque_id'],
            dateEmission: $data['date_emission'],
            statut:       isset($data['statut'])
                ? ChequeStatut::from((int) $data['statut'])
                : ChequeStatut::EMIS,
        );
    }
}