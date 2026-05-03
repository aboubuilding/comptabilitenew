<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\ParentEleve; // si besoin
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbandonService
{
    /**
     * Récupère l'année en session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des abandons pour l'année courante
     */
    public function listAbandons(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['data' => collect(), 'pagination' => []];
        }

        $query = Inscription::with(['eleve', 'parent', 'cycle', 'niveau', 'classe'])
            ->where('annee_id', $anneeId)
            ->where('statut_abandon', 1); // 1 = abandonné

        // Filtres
        if (!empty($filters['cycle_id'])) {
            $query->where('cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('niveau_id', $filters['niveau_id']);
        }
        if (!empty($filters['classe_id'])) {
            $query->where('classe_id', $filters['classe_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('matricule', 'like', $search);
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $abandons = $query->paginate($perPage);

        // Formatage
        $data = $abandons->map(fn($ins) => [
            'id'             => $ins->id,
            'eleve_nom'      => $ins->eleve?->nom . ' ' . $ins->eleve?->prenom,
            'matricule'      => $ins->eleve?->matricule,
            'cycle'          => $ins->cycle?->libelle,
            'niveau'         => $ins->niveau?->libelle,
            'classe'         => $ins->classe?->libelle,
            'date_abandon'   => $ins->date_abandon,
            'motif_abandon'  => $ins->motif_abandon,
            'parent'         => $ins->parent?->nom_parent . ' ' . $ins->parent?->prenom_parent,
        ]);

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $abandons->currentPage(),
                'last_page'    => $abandons->lastPage(),
                'per_page'     => $abandons->perPage(),
                'total'        => $abandons->total(),
            ]
        ];
    }

    /**
     * Marquer un élève comme abandonné
     *
     * @param int $inscriptionId
     * @param array $data (date_abandon, motif_abandon)
     * @param int $userId
     */
    public function marquerAbandon(int $inscriptionId, array $data, int $userId): Inscription
    {
        $inscription = Inscription::where('id', $inscriptionId)
            ->where('annee_id', $this->getCurrentAnneeId())
            ->firstOrFail();

        $inscription->date_abandon   = $data['date_abandon'];
        $inscription->motif_abandon  = $data['motif_abandon'];
        $inscription->statut_abandon = 1;
        $inscription->abandonne_par  = $userId;
        $inscription->save();

        return $inscription;
    }

    /**
     * Annuler l'abandon (réinscrire l'élève)
     */
    public function annulerAbandon(int $inscriptionId): Inscription
    {
        $inscription = Inscription::where('id', $inscriptionId)
            ->where('annee_id', $this->getCurrentAnneeId())
            ->firstOrFail();

        $inscription->date_abandon   = null;
        $inscription->motif_abandon  = null;
        $inscription->statut_abandon = 0;
        $inscription->abandonne_par  = null;
        $inscription->save();

        return $inscription;
    }

    /**
     * Statistiques des abandons pour l'année courante
     */
    public function getStats(): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['total_abandons' => 0];
        }

        $total = Inscription::where('annee_id', $anneeId)
            ->where('statut_abandon', 1)
            ->count();

        return [
            'total_abandons' => $total,
        ];
    }
}