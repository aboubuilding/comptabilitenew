<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\EntretienVehicule;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EntretienVehiculeRepository extends BaseRepository
{
    public function __construct(EntretienVehicule $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['voiture:id,plaque,marque,modele', 'chauffeur:id,nom,prenom'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['voiture_id'] ?? null, fn ($q, $v) => $q->where('voiture_id', $v))
            ->when($filters['type_entretien'] ?? null, fn ($q, $v) => $q->where('type_entretien', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('type_entretien', 'like', "%{$v}%")
                        ->orWhere('observations', 'like', "%{$v}%")
                        ->orWhereHas('voiture', fn ($c) => $c->where('plaque', 'like', "%{$v}%"));
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_entretien', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_entretien', '<=', $v))
            ->orderByDesc('date_entretien')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Types d'entretien déjà saisis (vidange, révision, pneus...), pour le filtre.
     *
     * @return string[]
     */
    public function typesDistincts(): array
    {
        return $this->activeQuery()
            ->whereNotNull('type_entretien')
            ->distinct()
            ->orderBy('type_entretien')
            ->pluck('type_entretien')
            ->all();
    }

    public function stats(?int $anneeId = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $q = $this->periode($anneeId, $dateDebut, $dateFin);

        return [
            'nb'         => (clone $q)->count(),
            'cout_total' => round((float) (clone $q)->sum('cout'), 2),
        ];
    }

    /** Nombre et coût des entretiens par type, du plus coûteux au moins coûteux. */
    public function parType(?int $anneeId = null, ?string $dateDebut = null, ?string $dateFin = null): Collection
    {
        return $this->periode($anneeId, $dateDebut, $dateFin)
            ->groupBy('type_entretien')
            ->selectRaw('type_entretien, COUNT(*) as nb, COALESCE(SUM(cout), 0) as cout')
            ->orderByDesc('cout')
            ->get();
    }

    private function periode(?int $anneeId, ?string $dateDebut, ?string $dateFin)
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('date_entretien', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('date_entretien', '<=', $v));
    }
}
