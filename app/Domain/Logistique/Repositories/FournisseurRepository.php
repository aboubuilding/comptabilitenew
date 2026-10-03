<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Fournisseur;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FournisseurRepository extends BaseRepository
{
    public function __construct(Fournisseur $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('raison_social', 'like', "%{$v}%")
                        ->orWhere('nom_contact', 'like', "%{$v}%")
                        ->orWhere('telephone_contact', 'like', "%{$v}%")
                        ->orWhere('email_contact', 'like', "%{$v}%");
                });
            })
            ->orderBy('raison_social')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Liste légère pour les <select> (module Achats 3.2). */
    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('raison_social')
            ->get(['id', 'raison_social', 'nom_contact']);
    }

    /** Charge un fournisseur + son historique d'achats (si le module existe). */
    public function findAvecHistorique(int $id): Fournisseur
    {
        $query = $this->activeQuery();

        if (Schema::hasTable('achats')) {
            $query->with(['achats' => fn ($q) => $q->where('etat', 1)
                ->orderByDesc('date_achat')
                ->orderByDesc('id')]);
        }

        return $query->findOrFail($id);
    }

    /** Top fournisseurs par montant d'achats — pour un futur dashboard. */
    public function topParMontant(int $limit = 5): Collection
    {
        if (! Schema::hasTable('achats')) {
            return collect();
        }

        return $this->activeQuery()
            ->withSum(['achats as montant_total' => fn ($q) => $q->where('etat', 1)], 'montant_total')
            ->orderByDesc('montant_total')
            ->limit($limit)
            ->get(['id', 'raison_social']);
    }
}