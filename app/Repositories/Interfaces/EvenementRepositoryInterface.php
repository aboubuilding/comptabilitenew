<?php

namespace App\Repositories\Interfaces;

use App\Models\Evenement;
use Illuminate\Database\Eloquent\Collection;

interface EvenementRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveEvenements(): Collection;
    public function getByType(string $type): Collection;
    public function getUpcoming(): Collection;
    public function getPast(): Collection;
    public function getByAnnee(int $anneeId): Collection;
    public function findByNom(string $nom): ?Evenement;
    public function canDelete(Evenement $evenement): bool;
    public function deleteWithCheck(Evenement $evenement): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): Evenement;
    public function updateWithValidation(Evenement $evenement, array $data): Evenement;

}
