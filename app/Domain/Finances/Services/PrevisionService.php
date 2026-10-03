<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\PrevisionData;
use App\Domain\Finances\Models\Prevision;
use App\Domain\Finances\Repositories\PrevisionRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PrevisionService
{
    public function __construct(
        private PrevisionRepository $previsions,
    ) {}

    public function creer(PrevisionData $data): Prevision
    {
        if ($data->montantPrevisionnel <= 0) {
            throw new RuntimeException("Le montant prévisionnel doit être strictement positif.");
        }

        return DB::transaction(fn () => $this->previsions->create([
            'libelle'              => $data->libelle,
            'type'                 => $data->type,
            'montant_previsionnel' => $data->montantPrevisionnel,
            'centre_analytique_id' => $data->centreAnalytiqueId,
            'date_debut'           => $data->dateDebut,
            'date_fin'             => $data->dateFin,
            'cree_par'             => $data->auteurId,
        ]));
    }

    public function modifier(int $id, PrevisionData $data): Prevision
    {
        $this->previsions->update($id, [
            'libelle'              => $data->libelle,
            'type'                 => $data->type,
            'montant_previsionnel' => $data->montantPrevisionnel,
            'centre_analytique_id' => $data->centreAnalytiqueId,
            'date_debut'           => $data->dateDebut,
            'date_fin'             => $data->dateFin,
        ]);

        return $this->previsions->findOrFail($id);
    }

    public function supprimer(int $id): void
    {
        $this->previsions->delete($id);
    }

    /**
     * Recalcule `montant_realise` de chaque prévision depuis les
     * imputations analytiques (source de vérité).
     */
    public function recalculerRealise(
        ?int $anneeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): int {
        // À implémenter côté RapportAnalytiqueService — voir plus bas.
        // Ce stub existe pour préparer le hook périodique.
        return 0;
    }
}