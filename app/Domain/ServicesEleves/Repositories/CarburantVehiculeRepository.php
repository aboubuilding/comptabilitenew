<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\CarburantVehicule;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CarburantVehiculeRepository extends BaseRepository
{
    public function __construct(CarburantVehicule $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('voiture:id,plaque,marque,modele')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['voiture_id'] ?? null, fn ($q, $v) => $q->where('voiture_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('station_service', 'like', "%{$v}%")
                        ->orWhere('facture', 'like', "%{$v}%")
                        ->orWhereHas('voiture', fn ($c) => $c->where('plaque', 'like', "%{$v}%"));
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_plein', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_plein', '<=', $v))
            ->orderByDesc('date_plein')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(?int $anneeId = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $q = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('date_plein', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('date_plein', '<=', $v));

        return [
            'nb'      => (clone $q)->count(),
            'litres'  => round((float) (clone $q)->sum('quantite_litres'), 2),
            'montant' => round((float) (clone $q)->sum('montant_total'), 2),
            'prix_moyen' => (function () use ($q) {
                $litres = (clone $q)->sum('quantite_litres');
                $montant = (clone $q)->sum('montant_total');
                return $litres > 0 ? round((float) $montant / $litres, 2) : 0;
            })(),
        ];
    }

    /** Consommation par véhicule (top 10). */
    public function parVehicule(?int $anneeId = null, ?string $dateDebut = null, ?string $dateFin = null)
    {
        return $this->activeQuery()
            ->with('voiture:id,plaque,marque')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('date_plein', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('date_plein', '<=', $v))
            ->groupBy('voiture_id')
            ->selectRaw('voiture_id, SUM(quantite_litres) as litres, SUM(montant_total) as montant, COUNT(*) as nb')
            ->orderByDesc('montant')
            ->limit(10)
            ->get();
    }
}