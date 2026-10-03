<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Bon;
use App\Domain\Logistique\Types\TypeBon;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BonRepository extends BaseRepository
{
    public function __construct(Bon $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'magasin:id,libelle,type',
                'magasinDestination:id,libelle,type',
                'utilisateur:id,name',
            ])
            ->withCount('details')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['magasin_id'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('magasin_id', $v)
                        ->orWhere('magasin_destination_id', $v);
                });
            })
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('reference', 'like', "%{$v}%")
                        ->orWhere('motif', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_bon', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_bon', '<=', $v))
            ->orderByDesc('date_bon')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecDetails(int $id): Bon
    {
        return $this->activeQuery()
            ->with([
                'magasin',
                'magasinDestination',
                'utilisateur:id,name',
                'details.produit:id,libelle,unite_stock',
            ])
            ->findOrFail($id);
    }

    /** Génère une référence unique : BEN-20260125-0001, BSO-..., BTR-... */
    public function genererReference(TypeBon $type): string
    {
        $prefix = match ($type) {
            TypeBon::ENTREE    => 'BEN',
            TypeBon::SORTIE    => 'BSO',
            TypeBon::TRANSFERT => 'BTR',
        };

        $prefix .= '-' . now()->format('Ymd') . '-';

        $dernier = $this->activeQuery()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function compterParType(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        return [
            'entree'    => (int) ($rows[TypeBon::ENTREE->value]    ?? 0),
            'sortie'    => (int) ($rows[TypeBon::SORTIE->value]    ?? 0),
            'transfert' => (int) ($rows[TypeBon::TRANSFERT->value] ?? 0),
        ];
    }
}