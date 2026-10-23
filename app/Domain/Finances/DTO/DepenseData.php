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
        public ?int    $ligneBudgetaireId = null,
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
            // « Hors ligne budgétaire » : aucune ligne, même si une valeur traîne dans le formulaire
            ligneBudgetaireId: ($data['imputation'] ?? null) !== 'hors_ligne' && ! empty($data['ligne_budgetaire_id'])
                ? (int) $data['ligne_budgetaire_id'] : null,
        );
    }
}