<?php

namespace App\Domain\Rh\Services;

use App\Domain\Rh\DTO\AssureDTO;
use App\Domain\Rh\Models\Assure;
use App\Domain\Rh\Repositories\AssureRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logique métier des assurances du personnel.
 */
class AssureService
{
    public function __construct(
        private readonly AssureRepository $repository
    ) {}

    /**
     * Souscrire une assurance pour un employé.
     *
     * @throws RuntimeException si l'employé est déjà assuré pour cette police.
     */
    public function souscrire(AssureDTO $dto): Assure
    {
        if ($this->repository->dejaAssure($dto->employe_id, $dto->assurance_id)) {
            throw new RuntimeException(
                "Cet employé est déjà assuré pour cette police."
            );
        }

        return DB::transaction(function () use ($dto) {
            return $this->repository->create($dto->toArray());
        });
    }

    /**
     * Mettre à jour une souscription existante.
     */
    public function mettreAJour(Assure $assure, AssureDTO $dto): Assure
    {
        // Si on change d'assurance, vérifier qu'il n'y a pas de doublon
        if ($assure->assurance_id !== $dto->assurance_id
            && $this->repository->dejaAssure($dto->employe_id, $dto->assurance_id)) {
            throw new RuntimeException(
                "Cet employé est déjà assuré pour cette police."
            );
        }

        return DB::transaction(function () use ($assure, $dto) {
            $this->repository->update($assure, $dto->toArray());
            return $assure->fresh(['employe', 'assurance']);
        });
    }

    /**
     * Désactiver (archiver logiquement) une souscription.
     */
    public function desactiver(Assure $assure): void
    {
        $this->repository->delete($assure);
    }

    /**
     * Statistiques pour le dashboard / index.
     */
    public function statistiques(): array
    {
        return [
            'total_assures'        => $this->repository->activeQuery()->count(),
            'total_actifs'         => $this->repository->activeQuery()->where('statut', 'actif')->count(),
            'total_prelevements'   => $this->repository->totalPrelevementsMensuels(),
        ];
    }
}