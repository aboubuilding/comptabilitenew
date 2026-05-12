<?php

namespace App\Services;

use App\Repositories\Eloquent\InscriptionRepository;
use Illuminate\Support\Collection;

class AbandonService extends BaseService
{
    protected InscriptionRepository $inscriptionRepo;

    public function __construct(InscriptionRepository $inscriptionRepo)
    {
        // On appelle le parent avec le même repository (optionnel)
        parent::__construct($inscriptionRepo);
        $this->inscriptionRepo = $inscriptionRepo;
    }

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
            return $this->formatResponse(true, 'Aucune année active', collect(), []);
        }

        // Utilisation du repository pour construire la requête
        $query = $this->inscriptionRepo->activeQuery()
            ->with(['eleve', 'parent', 'cycle', 'niveau', 'classe'])
            ->where('annee_id', $anneeId)
            ->where('statut_abandon', 1);

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

        // Formatage des données
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

        return $this->formatResponse(true, 'Liste des abandons', $data, [
            'pagination' => [
                'current_page' => $abandons->currentPage(),
                'last_page'    => $abandons->lastPage(),
                'per_page'     => $abandons->perPage(),
                'total'        => $abandons->total(),
            ]
        ]);
    }

    /**
     * Marquer un élève comme abandonné
     */
    public function marquerAbandon(int $inscriptionId, array $data, int $userId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie');
        }

        $inscription = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $anneeId)
            ->find($inscriptionId);

        if (!$inscription) {
            return $this->formatResponse(false, 'Inscription introuvable');
        }

        $inscription->date_abandon   = $data['date_abandon'];
        $inscription->motif_abandon  = $data['motif_abandon'];
        $inscription->statut_abandon = 1;
        $inscription->abandonne_par  = $userId;
        $inscription->save();

        return $this->formatResponse(true, 'Abandon enregistré', $inscription);
    }

    /**
     * Annuler l'abandon (réinscrire)
     */
    public function annulerAbandon(int $inscriptionId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie');
        }

        $inscription = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $anneeId)
            ->find($inscriptionId);

        if (!$inscription) {
            return $this->formatResponse(false, 'Inscription introuvable');
        }

        $inscription->date_abandon   = null;
        $inscription->motif_abandon  = null;
        $inscription->statut_abandon = 0;
        $inscription->abandonne_par  = null;
        $inscription->save();

        return $this->formatResponse(true, 'Abandon annulé', $inscription);
    }

    /**
     * Statistiques des abandons
     */
    public function getStats(): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return $this->formatResponse(true, '', ['total_abandons' => 0]);
        }

        $total = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $anneeId)
            ->where('statut_abandon', 1)
            ->count();

        return $this->formatResponse(true, '', ['total_abandons' => $total]);
    }
}
