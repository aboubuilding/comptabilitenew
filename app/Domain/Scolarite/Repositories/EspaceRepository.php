<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Espace;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EspaceRepository extends BaseRepository
{
    public function __construct(Espace $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $anneeId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->withCount('eleves')
            ->with([
                'parents' => fn ($q) => $q->orderByDesc('is_principal'),
                'comptes' => fn ($q) => $q->latest(),
            ])
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('nom_famille', 'like', "%{$s}%")
                        ->orWhereHas('parents', function ($q) use ($s) {
                            $q->where('nom_parent', 'like', "%{$s}%")
                                ->orWhere('prenom_parent', 'like', "%{$s}%")
                                ->orWhere('telephone', 'like', "%{$s}%");
                        });
                });
            })
            ->orderBy('nom_famille')
            ->paginate($perPage);
    }
}