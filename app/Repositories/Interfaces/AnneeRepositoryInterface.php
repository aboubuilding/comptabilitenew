<?php
// app/Repositories/Interfaces/AnneeRepositoryInterface.php

namespace App\Repositories\Interfaces;

use App\Models\Annee;
use Illuminate\Database\Eloquent\Collection;

interface AnneeRepositoryInterface
{
    public function getAllWithFilters(?array $filters = null): Collection;
    public function getActiveYears(): Collection;
    public function getByStatus(int $statut): Collection;
    public function getCurrentYear(): ?Annee;
    public function getDefaultYear(): ?Annee;
    public function setAsOpen(Annee $annee): Annee;
    public function changeStatus(Annee $annee, int $status): Annee;
    public function canDelete(Annee $annee): bool;
    public function deleteWithCheck(Annee $annee): bool;
    public function getStats(): array;
    public function createWithValidation(array $data): Annee;
    public function updateWithValidation(Annee $annee, array $data): Annee;


}
