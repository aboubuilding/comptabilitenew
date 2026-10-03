<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\FraisEcole;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FraisEcoleRepository extends BaseRepository
{
    public function __construct(FraisEcole $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['niveau:id,libelle', 'planEcheancier:id,nom'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->where('niveau_id', $v))
            ->when($filters['modalite_paiement'] ?? null, fn ($q, $v) => $q->where('modalite_paiement', $v))
            ->when($filters['type_forfait'] ?? null, fn ($q, $v) => $q->where('type_forfait', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('niveau_id')
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(?int $anneeId = null)
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'montant', 'type_paiement']);
    }

    public function compterParTypePaiement(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('modalite_paiement, COUNT(*) as total')
            ->groupBy('modalite_paiement')
            ->pluck('total', 'modalite_paiement')
            ->all();

        return [
            'comptant'  => (int) ($rows[\App\Domain\Finances\Types\TypePaiement::COMPTANT->value] ?? 0),
            'echelonne' => (int) ($rows[\App\Domain\Finances\Types\TypePaiement::ECHELONNE->value] ?? 0),
        ];
    }
}