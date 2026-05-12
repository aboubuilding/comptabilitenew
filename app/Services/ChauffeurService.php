<?php

namespace App\Services;

use App\Repositories\Eloquent\ChauffeurRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChauffeurService
{
    protected ChauffeurRepository $chauffeurRepo;

    public function __construct(ChauffeurRepository $chauffeurRepo)
    {
        $this->chauffeurRepo = $chauffeurRepo;
    }

    /**
     * Récupère l'année en session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste paginée des chauffeurs
     */
    public function listChauffeurs(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = $this->chauffeurRepo->activeQuery() // filtre etat = 1 (actif non supprimé)
        ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(isset($filters['statut']) && $filters['statut'] !== '', fn($q) => $q->where('statut', $filters['statut']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', $search)
                        ->orWhere('prenom', 'like', $search)
                        ->orWhere('permis_conduire', 'like', $search)
                        ->orWhere('telephone', 'like', $search);
                });
            });

        $perPage = $filters['per_page'] ?? 15;
        $chauffeurs = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage);

        $data = $chauffeurs->map(fn($c) => [
            'id'                 => $c->id,
            'nom'                => $c->nom,
            'prenom'             => $c->prenom,
            'permis_conduire'    => $c->permis_conduire,
            'date_validite_permis' => $c->date_validite_permis?->format('d/m/Y'),
            'telephone'          => $c->telephone,
            'email'              => $c->email,
            'adresse'            => $c->adresse,
            'statut'             => $c->statut,
            'statut_label'       => $c->statut ? 'Actif' : 'Inactif',
            'created_at'         => $c->created_at?->format('d/m/Y H:i'),
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $chauffeurs->currentPage(),
                'last_page'    => $chauffeurs->lastPage(),
                'per_page'     => $chauffeurs->perPage(),
                'total'        => $chauffeurs->total(),
            ]
        ];
    }

    /**
     * Récupère un chauffeur par ID
     */
    public function getChauffeur(int $id): \App\Models\Chauffeur
    {
        return $this->chauffeurRepo->findOrFail($id);
    }

    /**
     * Crée un chauffeur
     */
    public function createChauffeur(array $data): \App\Models\Chauffeur
    {
        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception('Année scolaire non définie en session.');
        }
        $data['annee_id'] = $anneeId;
        $data['statut'] = $data['statut'] ?? 1;
        $data['etat'] = 1; // actif pour le soft delete

        return $this->chauffeurRepo->create($data);
    }

    /**
     * Met à jour un chauffeur
     */
    public function updateChauffeur(int $id, array $data): \App\Models\Chauffeur
    {
        $this->chauffeurRepo->update($id, $data);
        return $this->chauffeurRepo->find($id);
    }

    /**
     * Supprime (soft delete) – on désactive plutôt
     */
    public function deleteChauffeur(int $id): void
    {
        $this->chauffeurRepo->update($id, ['statut' => 0]);
    }

    /**
     * Liste pour select (dropdown)
     */
    public function getForSelect(): Collection
    {
        return $this->chauffeurRepo->activeQuery()
            ->where('statut', 1)
            ->orderBy('nom')
            ->orderBy('prenom')
            ->get(['id', 'nom', 'prenom'])
            ->map(fn($c) => [
                'id'   => $c->id,
                'nom'  => $c->nom . ' ' . $c->prenom,
            ]);
    }

    /**
     * Vérifie si le chauffeur a des affectations en cours
     */
    public function hasActiveAffectations(int $id): bool
    {
        return DB::table('affectations_vehicules')
            ->where('chauffeur_id', $id)
            ->whereNull('date_fin')
            ->exists();
    }
}
