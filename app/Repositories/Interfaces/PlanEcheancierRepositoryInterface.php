<?php

namespace App\Repositories\Interfaces;

use App\Models\PlanEcheancier;
use Illuminate\Database\Eloquent\Collection;

interface PlanEcheancierRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActivePlans(): Collection;
    public function getByAnnee(int $anneeId): Collection;
    public function findWithLignes(int $id): ?PlanEcheancier;
    public function findByNom(string $nom): ?PlanEcheancier;
    public function canDelete(PlanEcheancier $plan): bool;
    public function deleteWithCheck(PlanEcheancier $plan): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): PlanEcheancier;
    public function updateWithValidation(PlanEcheancier $plan, array $data): PlanEcheancier;

}
