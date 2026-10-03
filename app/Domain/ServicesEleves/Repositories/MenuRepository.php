<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Menu;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MenuRepository extends BaseRepository
{
    public function __construct(Menu $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['magasin:id,libelle'])
            ->withCount(['menuProduits', 'preparations'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['type_repas'] ?? null, fn ($q, $v) => $q->where('type_repas', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_service', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_service', '<=', $v))
            ->orderByDesc('date_service')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Menus de la semaine en cours (pour l'affichage principal). */
    public function menusSemaine(): Collection
    {
        return $this->activeQuery()
            ->with(['menuProduits.produit:id,libelle,unite_stock'])
            ->whereBetween('date_service', [now()->startOfWeek(), now()->endOfWeek()])
            ->orderBy('date_service')
            ->get();
    }

    /** Menu du jour. */
    public function menuDuJour(?string $type = null): Collection
    {
        $q = $this->activeQuery()
            ->with(['menuProduits.produit:id,libelle,unite_stock'])
            ->whereDate('date_service', now()->toDateString())
            ->orderBy('type_repas');

        return $q->get();
    }

    public function findAvecProduits(int $id): Menu
    {
        return $this->activeQuery()
            ->with([
                'menuProduits.produit:id,libelle,unite_stock,prix_unitaire',
                'preparations.responsable:id,name',
                'magasin:id,libelle',
            ])
            ->findOrFail($id);
    }
}