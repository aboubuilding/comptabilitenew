<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Cheque;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChequeRepository extends BaseRepository
{
    public function __construct(Cheque $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'banque:id,nom',
                'rapprocheur:id,name',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', (int) $filters['statut'])
            )
            ->when($filters['banque_id'] ?? null, fn ($q, $v) => $q->where('banque_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('numero', 'like', "%{$v}%")
                        ->orWhere('emetteur', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_emission', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_emission', '<=', $v))
            ->orderByDesc('date_emission')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /* ===== Compteurs pour les cartes-statistiques de l'index ===== */

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return [
            'emis'     => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::EMIS->value]     ?? 0),
            'encaisse' => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::ENCAISSE->value] ?? 0),
            'rejete'   => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::REJETE->value]   ?? 0),
        ];
    }

    public function montantEnAttente(): float
    {
        return round(
            (float) $this->activeQuery()
                ->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS->value)
                ->sum('montant'),
            2
        );
    }

    public function findAvecRelations(int $id): Cheque
    {
        return $this->activeQuery()
            ->with(['banque:id,nom', 'rapprocheur:id,name'])
            ->findOrFail($id);
    }
}