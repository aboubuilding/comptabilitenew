<?php

namespace App\Repositories\Interfaces;

use App\Models\Cycle;
use Illuminate\Database\Eloquent\Collection;

interface CycleRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveCycles(): Collection;
    public function findByLibelle(string $libelle): ?Cycle;
    public function canDelete(Cycle $cycle): bool;
    public function deleteWithCheck(Cycle $cycle): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): Cycle;
    public function updateWithValidation(Cycle $cycle, array $data): Cycle;

}
