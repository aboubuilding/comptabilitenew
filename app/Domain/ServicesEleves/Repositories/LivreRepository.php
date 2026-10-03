<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Livre;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LivreRepository extends BaseRepository
{
    public function __construct(Livre $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'auteur:id,nom_prenom',
                'categorie:id,libelle',
                'maisonEdition:id,libelle',
                'anneeEdition:id,libelle',
            ])
            ->when($filters['categorie_livre_id'] ?? null, fn ($q, $v) => $q->where('categorie_livre_id', $v))
            ->when($filters['auteur_id'] ?? null, fn ($q, $v) => $q->where('auteur_id', $v))
            ->when($filters['disponible'] ?? null, fn ($q, $v) => $q->where('nombre_disponibles', '>', 0))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('titre', 'like', "%{$v}%")
                        ->orWhere('numero', 'like', "%{$v}%")
                        ->orWhere('numero_isbn', 'like', "%{$v}%")
                        ->orWhereHas('auteur', fn ($a) => $a->where('nom_prenom', 'like', "%{$v}%"));
                });
            })
            ->orderBy('titre')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('titre')
            ->get(['id', 'titre', 'numero', 'nombre_disponibles']);
    }

    public function findAvecRelations(int $id): Livre
    {
        return $this->activeQuery()
            ->with([
                'auteur:id,nom_prenom',
                'categorie:id,libelle',
                'maisonEdition:id,libelle',
                'anneeEdition:id,libelle',
                'prets' => fn ($q) => $q->orderByDesc('date_pret')->limit(20),
                'prets.inscription.eleve:id,nom,prenom,matricule',
            ])
            ->findOrFail($id);
    }

    public function compterStatistiques(): array
    {
        return [
            'total_livres'        => $this->activeQuery()->count(),
            'total_exemplaires'   => (int) $this->activeQuery()->sum('nombre_exemplaires'),
            'total_disponibles'   => (int) $this->activeQuery()->sum('nombre_disponibles'),
            'livres_indisponibles'=> $this->activeQuery()->where('nombre_disponibles', 0)->count(),
        ];
    }
}