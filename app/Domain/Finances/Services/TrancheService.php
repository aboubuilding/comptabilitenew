<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\Tranche;
use App\Domain\Finances\Repositories\TrancheRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TrancheService
{
    public function __construct(
        private TrancheRepository $tranches,
    ) {}

    /**
     * Créer une tranche. Contrainte : la somme des taux d'un même frais
     * ne peut pas dépasser 100 %.
     */
    public function creer(array $data): Tranche
    {
        $fraisId = (int) $data['frais_ecole_id'];
        $taux    = (float) $data['taux'];

        if ($taux <= 0 || $taux > 100) {
            throw new RuntimeException("Le taux doit être compris entre 0 et 100 %.");
        }

        $totalActuel = $this->tranches->totalTauxPourFrais($fraisId);
        if ($totalActuel + $taux > 100.01) {
            throw new RuntimeException(
                "La somme des taux de ce frais dépasserait 100 % " .
                "(actuellement {$totalActuel} %, cette tranche ajouterait {$taux} %)."
            );
        }

        return DB::transaction(fn () => $this->tranches->create([
            'libelle'      => $data['libelle'],
            'date_butoire' => $data['date_butoire'],
            'frais_ecole_id' => $fraisId,
            'type_frais'   => $data['type_frais'] ?? null,
            'taux'         => $taux,
        ]));
    }

    public function modifier(int $id, array $data): Tranche
    {
        $tranche = $this->tranches->findOrFail($id);
        $taux    = (float) $data['taux'];

        if ($taux <= 0 || $taux > 100) {
            throw new RuntimeException("Le taux doit être compris entre 0 et 100 %.");
        }

        $totalAutres = $this->tranches->totalTauxPourFrais($tranche->frais_ecole_id, ignoreId: $id);
        if ($totalAutres + $taux > 100.01) {
            throw new RuntimeException(
                "La somme des taux de ce frais dépasserait 100 % " .
                "(autres tranches : {$totalAutres} %, cette tranche : {$taux} %)."
            );
        }

        $this->tranches->update($id, [
            'libelle'        => $data['libelle'],
            'date_butoire'   => $data['date_butoire'],
            'frais_ecole_id' => $data['frais_ecole_id'],
            'type_frais'     => $data['type_frais'] ?? null,
            'taux'           => $taux,
        ]);

        return $tranche->refresh();
    }

    public function supprimer(int $id): void
    {
        $this->tranches->delete($id);
    }
}