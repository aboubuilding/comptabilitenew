<?php

namespace App\Domain\Finances\DTO;

final readonly class PaiementData
{
    /**
     * @param PanierLigneData[] $lignes
     */
    public function __construct(
        public int    $inscriptionId,
        public string $payeur,
        public ?string $telephonePayeur,
        public int    $modePaiement,
        public array  $lignes,
        public int    $comptableId,
        /** Références du chèque (numero, banque_id, emetteur, date_emission) si le paiement est fait par chèque. */
        public ?array $cheque = null,
    ) {}

    public static function fromRequest(array $data, int $comptableId): self
    {
        return new self(
            inscriptionId:    (int) $data['inscription_id'],
            payeur:           $data['payeur'],
            telephonePayeur:  $data['telephone_payeur'] ?? null,
            modePaiement:     (int) $data['mode_paiement'],
            lignes:           array_map(fn ($l) => PanierLigneData::fromArray($l), $data['lignes']),
            comptableId:      $comptableId,
            cheque:           ! empty($data['cheque']['numero']) ? $data['cheque'] : null,
        );
    }

    public function montantTotal(): float
    {
        return round(array_sum(array_map(fn (PanierLigneData $l) => $l->montant, $this->lignes)), 2);
    }
}