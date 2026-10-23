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
            throw new RuntimeException("Le montant global doit être strictement positif.");
        }

        // Une ligne budgétaire porte des dépenses : plus de type, ni de période (l'année suffit)
        return DB::transaction(fn () => $this->previsions->create([
            'libelle'              => $data->libelle,
            'type'                 => \App\Domain\Finances\Types\TypePrevision::DEPENSE->value,
            'montant_previsionnel' => $data->montantPrevisionnel,
            'centre_analytique_id' => $data->centreAnalytiqueId,
            'cree_par'             => $data->auteurId,
        ]));
    }

    public function modifier(int $id, PrevisionData $data): Prevision
    {
        if ($data->montantPrevisionnel <= 0) {
            throw new RuntimeException("Le montant global doit être strictement positif.");
        }

        $this->previsions->update($id, [
            'libelle'              => $data->libelle,
            'montant_previsionnel' => $data->montantPrevisionnel,
            'centre_analytique_id' => $data->centreAnalytiqueId,
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