<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Repositories\BaseRepository;

class AnneeRepository extends BaseRepository
{
    public function __construct(Annee $model)
    {
        parent::__construct($model);
    }

    public function findOuverte(): ?Annee
    {
        return $this->activeQuery()
            ->where('statut_annee', StatutAnnee::Ouvert->value)
            ->first();
    }

    public function findLastByDateRentree(): ?Annee
    {
        return $this->activeQuery()
            ->orderBy('date_rentree', 'desc')
            ->first();
    }

    /**
     * Liste pour le <select> du header — années actives, les plus
     * récentes en premier.
     */
    public function allForSelector(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeQuery()
            ->orderBy('date_rentree', 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('libelle', 'like', "%{$s}%"))
            ->orderBy('date_rentree', 'desc')
            ->paginate($perPage);
    }

    /**
     * Persistance pure (changement de statut) — la décision "peut-on
     * clôturer cette année ?" reste dans AnneeService, pas ici.
     */
    public function cloturer(int $anneeId): bool
    {
        return $this->update($anneeId, ['statut_annee' => StatutAnnee::Cloture->value]);
    }
}