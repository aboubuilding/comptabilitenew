<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Activite;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ActiviteRepository extends BaseRepository
{
    public function __construct(Activite $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['niveau:id,libelle'])
            ->withCount('inscriptions')
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->where('niveau_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'montant', 'type']);
    }

    public function compterStatistiques(): array
    {
        return [
            'total'      => $this->activeQuery()->count(),
            'sportives'  => $this->activeQuery()->where('type', 'sportive')->count(),
            'culturelles'=> $this->activeQuery()->where('type', 'culturelle')->count(),
            'autres'     => $this->activeQuery()->whereNotIn('type', ['sportive', 'culturelle'])->count(),
        ];
    }
}