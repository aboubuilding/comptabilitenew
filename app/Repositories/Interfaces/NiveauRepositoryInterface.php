<?php

namespace App\Repositories\Interfaces;

use App\Models\Niveau;
use Illuminate\Database\Eloquent\Collection;

interface NiveauRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveNiveaux(): Collection;
    public function getByCycle(int $cycleId): Collection;
    public function getOrderedNiveaux(): Collection;
    public function findByLibelle(string $libelle): ?Niveau;
    public function canDelete(Niveau $niveau): bool;
    public function deleteWithCheck(Niveau $niveau): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): Niveau;
    public function updateWithValidation(Niveau $niveau, array $data): Niveau;

}
