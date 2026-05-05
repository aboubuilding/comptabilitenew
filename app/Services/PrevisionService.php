<?php
namespace App\Services;

use App\Models\Prevision;
use Illuminate\Support\Facades\DB;

class PrevisionService
{
    protected function getCurrentAnneeId()
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste paginée des prévisions + agrégats par type
     */
    public function listPrevisions(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = Prevision::with('annee')
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

        // Agrégats : total recettes, total dépenses, solde prévisionnel
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

    /**
     * Créer une prévision
     */
    public function createPrevision(array $data): Prevision
    {
        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception("Année scolaire non définie.");
        }
        $data['annee_id'] = $anneeId;
        return Prevision::create($data);
    }

    /**
     * Mettre à jour une prévision
     */
    public function updatePrevision(int $id, array $data): Prevision
    {
        $prevision = Prevision::findOrFail($id);
        $prevision->update($data);
        return $prevision;
    }

    /**
     * Supprimer une prévision (soft delete = etat=0)
     */
    public function deletePrevision(int $id): void
    {
        $prevision = Prevision::findOrFail($id);
        $prevision->etat = 0;
        $prevision->save();
    }

    /**
     * Récupérer une prévision
     */
    public function getPrevision(int $id): Prevision
    {
        return Prevision::findOrFail($id);
    }
}