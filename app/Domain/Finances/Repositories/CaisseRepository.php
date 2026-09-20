<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Types\CaisseStatut;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CaisseRepository extends BaseRepository
{
    public function __construct(Caisse $model)
    {
        parent::__construct($model);
    }

    /** Liste paginée pour l'écran index (filtres + eager load caissier). */
    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['caissier:id,nom,prenom'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', (int) $filters['statut'])
            )
            ->when($filters['responsable_id'] ?? null, fn ($q, $v) => $q->where('responsable_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderByDesc('date_ouverture')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Charge la caisse + tout ce qu'il faut pour la vue show (journal). */
    public function findAvecJournal(int $id): Caisse
    {
        return $this->activeQuery()
            ->with([
                'caissier:id,nom,prenom',
                'valideur:id,nom,prenom',
                'mouvements' => fn ($q) => $q->orderBy('date_mouvement')->orderBy('id'),
                'mouvements.utilisateur:id,nom,prenom',
            ])
            ->findOrFail($id);
    }

    /** Règle métier : un caissier ne peut avoir qu'une seule caisse ouverte. */
    public function findOuverteParCaissier(int $caissierId): ?Caisse
    {
        return $this->activeQuery()
            ->where('responsable_id', $caissierId)
            ->where('statut', CaisseStatut::OUVERTE->value)
            ->latest('date_ouverture')
            ->first();
    }

    /** Nombre de sessions actuellement ouvertes (cartes stats du index). */
    public function compterOuvertes(): int
    {
        return $this->activeQuery()
            ->where('statut', CaisseStatut::OUVERTE->value)
            ->count();
    }

    /** Liste légère pour peupler les <select>. */
    public function listePourSelect(?int $anneeId = null): Collection
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderByDesc('id')
            ->get(['id', 'libelle', 'statut']);
    }
}