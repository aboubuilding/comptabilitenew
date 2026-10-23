<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Types\DepenseStatut;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepenseRepository extends BaseRepository
{
    public function __construct(Depense $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'auteur:id,name',
                'valideur:id,name',
                'caisse:id,libelle',
                'ligneBudgetaire:id,libelle',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut_depense', (int) $filters['statut'])
            )
            ->when($filters['caisse_id'] ?? null, fn ($q, $v) => $q->where('caisse_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('libelle', 'like', "%{$v}%")
                        ->orWhere('beneficiaire', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_depense', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_depense', '<=', $v))
            ->orderByDesc('date_depense')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /* ===================== Compteurs pour les cartes stats ===================== */

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut_depense, COUNT(*) as total')
            ->groupBy('statut_depense')
            ->pluck('total', 'statut_depense')
            ->all();

        return [
            'attente' => (int) ($rows[DepenseStatut::EN_ATTENTE->value] ?? 0),
            'validee' => (int) ($rows[DepenseStatut::VALIDEE->value]    ?? 0),
            'rejetee' => (int) ($rows[DepenseStatut::REJETEE->value]    ?? 0),
        ];
    }

    public function montantParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut_depense, SUM(montant) as total')
            ->groupBy('statut_depense')
            ->pluck('total', 'statut_depense')
            ->all();

        return [
            'attente' => round((float) ($rows[DepenseStatut::EN_ATTENTE->value] ?? 0), 2),
            'validee' => round((float) ($rows[DepenseStatut::VALIDEE->value]    ?? 0), 2),
            'rejetee' => round((float) ($rows[DepenseStatut::REJETEE->value]    ?? 0), 2),
        ];
    }

    /** Dépenses validées non encore réglées (file d'attente du caissier). */
    public function aReglerPourCaisse(int $caisseId)
    {
        return $this->activeQuery()
            ->with(['auteur:id,name'])
            ->where('statut_depense', DepenseStatut::VALIDEE->value)
            ->whereNull('date_reglement')
            ->orderBy('date_depense')
            ->get();
    }
}