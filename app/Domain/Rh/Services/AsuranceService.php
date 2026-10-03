<?php

namespace App\Domain\Rh\Services;

use App\Domain\Rh\DTO\AsuranceDTO;
use App\Domain\Rh\Models\Asurance;
use App\Domain\Rh\Repositories\AsuranceRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logique métier du catalogue des assurances.
 */
class AsuranceService
{
    public function __construct(
        private readonly AsuranceRepository $repository
    ) {}

    public function creer(AsuranceDTO $dto): Asurance
    {
        return DB::transaction(function () use ($dto) {
            return $this->repository->create($dto->toArray());
        });
    }

    public function mettreAJour(Asurance $asurance, AsuranceDTO $dto): Asurance
    {
        return DB::transaction(function () use ($asurance, $dto) {
            $this->repository->update($asurance, $dto->toArray());
            return $asurance->fresh();
        });
    }

    /**
     * Désactive une assurance du catalogue.
     *
     * @throws RuntimeException si des assurés actifs y sont rattachés.
     */
    public function desactiver(Asurance $asurance): void
    {
        if ($this->repository->aDesAssuresActifs($asurance->id)) {
            throw new RuntimeException(
                "Impossible de désactiver cette assurance : des assurés actifs y sont rattachés."
            );
        }

        $this->repository->delete($asurance);
    }
}