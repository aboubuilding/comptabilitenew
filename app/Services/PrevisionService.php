<?php
namespace App\Services;

use App\Repositories\Interfaces\PrevisionRepositoryInterface;

class PrevisionService extends BaseService
{
    protected string $entityName = 'Prévision';
    protected array $defaultSelectFields = [
        'id', 'libelle', 'type', 'montant', 'date_prevision', 'date_fin',
        'periode', 'annee_id', 'commentaire', 'etat'
    ];

    public function __construct(PrevisionRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste paginée des prévisions + agrégats par type
     */
    public function listPrevisions(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = $this->repo->activeQuery()
            ->with('annee')
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $q->where('libelle', 'like', $search);
            })
            ->when(!empty($filters['date_debut']), fn($q) => $q->whereDate('date_prevision', '>=', $filters['date_debut']))
            ->when(!empty($filters['date_fin']), fn($q) => $q->whereDate('date_prevision', '<=', $filters['date_fin']));

        $perPage = $filters['per_page'] ?? 15;
        $previsions = $query->orderBy('date_prevision', 'desc')->paginate($perPage);

        $data = $previsions->map(fn($p) => [
            'id'            => $p->id,
            'libelle'       => $p->libelle,
            'type'          => $p->type,
            'type_label'    => $p->type == 'recette' ? 'Recette' : 'Dépense',
            'montant'       => $p->montant,
            'date_prevision'=> $p->date_prevision->format('Y-m-d'),
            'date_fin'      => $p->date_fin?->format('Y-m-d'),
            'periode'       => $p->periode,
            'annee'         => $p->annee?->libelle,
            'commentaire'   => $p->commentaire,
        ]);

        $totalRecettes = (clone $query)->where('type', 'recette')->sum('montant');
        $totalDepenses = (clone $query)->where('type', 'depense')->sum('montant');

        return [
            'data'       => $data,
            'aggregates' => [
                'total_recettes' => $totalRecettes,
                'total_depenses' => $totalDepenses,
                'solde_previsionnel' => $totalRecettes - $totalDepenses,
            ],
            'pagination' => [
                'current_page' => $previsions->currentPage(),
                'last_page'    => $previsions->lastPage(),
                'per_page'     => $previsions->perPage(),
                'total'        => $previsions->total(),
            ]
        ];
    }

    public function createPrevision(array $data): array
    {
        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie.');
        }
        $data['annee_id'] = $anneeId;
        return $this->store($data);
    }

    public function updatePrevision(int $id, array $data): array
    {
        return $this->update($id, $data);
    }

    public function deletePrevision(int $id): array
    {
        return $this->destroy($id);
    }

    public function getPrevision(int $id): array
    {
        return $this->show($id);
    }
}
