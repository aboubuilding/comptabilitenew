<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Banque;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BanqueRepository extends BaseRepository
{
    public function __construct(Banque $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->withCount([
                'cheques as cheques_total',
                'cheques as cheques_en_attente' => fn ($q) => $q->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS->value),
            ])
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nom', 'like', "%{$v}%"))
            ->orderBy('nom')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Liste légère pour les <select> (nouveau chèque, filtres). */
    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom')
            ->get(['id', 'nom']);
    }

    /** Détail d'une banque + ses chèques (vue show si tu en ajoutes une). */
    public function findAvecCheques(int $id): Banque
    {
        return $this->activeQuery()
            ->with(['cheques' => fn ($q) => $q->orderByDesc('date_emission')->orderByDesc('id')])
            ->findOrFail($id);
    }
}