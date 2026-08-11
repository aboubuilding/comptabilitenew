<?php

namespace App\Repositories\Interfaces;

use App\Models\FraisEcole;
use Illuminate\Database\Eloquent\Collection;

interface FraisEcoleRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveFrais(): Collection;
    public function getByNiveau(int $niveauId): Collection;
    public function getByAnnee(int $anneeId): Collection;
    public function getByType(int $type): Collection;
    public function getAvecEcheancier(): Collection;
    public function getSansEcheancier(): Collection;
    public function findByLibelle(string $libelle): ?FraisEcole;
    public function canDelete(FraisEcole $frais): bool;
    public function deleteWithCheck(FraisEcole $frais): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): FraisEcole;
    public function updateWithValidation(FraisEcole $frais, array $data): FraisEcole;

}
