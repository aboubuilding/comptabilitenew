<?php

// app/Domain/Finances/Repositories/RegleVentilationRepository.php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\RegleVentilation;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RegleVentilationRepository extends BaseRepository
{
    public function __construct(RegleVentilation $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('centre:id,code,libelle')
            ->when($filters['nature'] ?? null, fn ($q, $v) => $q->where('nature', $v))
            ->when($filters['sens'] ?? null,   fn ($q, $v) => $q->where('sens', $v))
            ->orderBy('nature')->orderBy('priorite')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Règles actives pour une nature/sens donnés, triées par priorité. */
    public function pourNature(string $nature, string $sens): Collection
    {
        return $this->activeQuery()
            ->with('centre:id,code,libelle')
            ->where('nature', $nature)
            ->where('sens', $sens)
            ->orderBy('priorite')
            ->get();
    }

    /** Natures distinctes déjà configurées (pour le filtre). */
    public function naturesDistinctes(): array
    {
        return $this->activeQuery()
            ->distinct()
            ->orderBy('nature')
            ->pluck('nature')
            ->all();
    }
}