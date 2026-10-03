<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Models\ModeleMessage;
use App\Domain\Administration\Repositories\ModeleMessageRepository;
use Illuminate\Support\Facades\DB;

class ModeleMessageService
{
    public function __construct(
        private readonly ModeleMessageRepository $repository
    ) {}

    public function creer(array $data): ModeleMessage
    {
        return DB::transaction(fn () => $this->repository->create($data));
    }

    public function mettreAJour(ModeleMessage $modele, array $data): ModeleMessage
    {
        return DB::transaction(function () use ($modele, $data) {
            $this->repository->update($modele, $data);
            return $modele->fresh();
        });
    }

    public function supprimer(ModeleMessage $modele): void
    {
        $this->repository->delete($modele);
    }
}