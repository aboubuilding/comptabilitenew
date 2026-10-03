<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\CentreAnalytique;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CentreAnalytiqueRepository extends BaseRepository
{
    public function __construct(CentreAnalytique $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('responsable:id,name')
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('code', 'like', "%{$v}%")
                        ->orWhere('libelle', 'like', "%{$v}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderBy('code')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('code')
            ->get(['id', 'code', 'libelle']);
    }
}