<?php

namespace App\Domain\Logistique\DTO;

final readonly class VenteData
{
    /**
     * @param VenteLigneData[] $lignes
     */
    public function __construct(
        public string  $dateVente,
        public int     $magasinId,
        public int     $typeVente,
        public ?int    $clientId,
        public ?int    $inscriptionId,
        public array   $lignes,
        public int     $utilisateurId,
        public bool    $marquerPayee = true,
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            dateVente:     $data['date_vente'] ?? now()->toDateString(),
            magasinId:     (int) $data['magasin_id'],
            typeVente:     (int) ($data['type_vente'] ?? 1),
            clientId:      isset($data['client_id']) ? (int) $data['client_id'] : null,
            inscriptionId: isset($data['inscription_id']) ? (int) $data['inscription_id'] : null,
            lignes:        array_map(fn (array $l) => VenteLigneData::fromArray($l), $data['lignes']),
            utilisateurId: $userId,
            marquerPayee:  (bool) ($data['marquer_payee'] ?? true),
        );
    }

    public function totalHt(): float
    {
        return round(array_sum(array_map(fn (VenteLigneData $l) => $l->montantLigne(), $this->lignes)), 2);
    }

    public function totalTtc(): float
    {
        // Pas de TVA pour l'instant (CDC ne le mentionne pas)
        return $this->totalHt();
    }
}