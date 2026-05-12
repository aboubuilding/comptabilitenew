<?php

namespace App\Services;

use App\Repositories\Eloquent\UserRepository;
use Illuminate\Support\Facades\Hash;

class ComptableService
{
    const ROLE_COMPTABLE = 2;

    protected UserRepository $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * Liste des comptables (rôle comptable) avec pagination
     */
    public function listeComptables(array $filters = []): array
    {
        // On utilise getModel()->newQuery() pour obtenir le query builder brut
        $query = $this->userRepo->getModel()->newQuery()
            ->where('role', self::ROLE_COMPTABLE)
            ->where('etat', 1);

        if (isset($filters['etat']) && in_array($filters['etat'], [0, 1])) {
            $query->where('etat', $filters['etat']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                    ->orWhere('prenom', 'like', $search)
                    ->orWhere('login', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $comptables = $query->orderBy('nom')->paginate($perPage);

        $data = $comptables->map(function ($user) {
            return [
                'id'         => $user->id,
                'nom'        => $user->nom,
                'prenom'     => $user->prenom,
                'login'      => $user->login,
                'email'      => $user->email,
                'photo'      => $user->photo,
                'etat'       => $user->etat,
                'etat_label' => $user->etat ? 'Actif' : 'Suspendu',
                'created_at' => $user->created_at->format('d/m/Y H:i'),
            ];
        });

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $comptables->currentPage(),
                'last_page'    => $comptables->lastPage(),
                'per_page'     => $comptables->perPage(),
                'total'        => $comptables->total(),
            ]
        ];
    }

    /**
     * Récupère un comptable par son ID
     */
    public function getComptable(int $id)
    {
        return $this->userRepo->getModel()->newQuery()
            ->where('role', self::ROLE_COMPTABLE)
            ->findOrFail($id);
    }

    /**
     * Crée un nouveau comptable
     */
    public function createComptable(array $data)
    {
        $data['role'] = self::ROLE_COMPTABLE;
        $data['mot_passe'] = Hash::make($data['mot_passe']);
        $data['etat'] = $data['etat'] ?? 1;
        $data['etat'] = 1;
        return $this->userRepo->create($data);
    }

    /**
     * Met à jour un comptable
     */
    public function updateComptable(int $id, array $data)
    {
        $comptable = $this->getComptable($id);
        if (!empty($data['mot_passe'])) {
            $data['mot_passe'] = Hash::make($data['mot_passe']);
        } else {
            unset($data['mot_passe']);
        }
        $this->userRepo->update($id, $data);
        return $this->getComptable($id);
    }

    /**
     * Suspendre un comptable (état = 0)
     */
    public function suspendre(int $id): void
    {
        $this->userRepo->update($id, ['etat' => 0]);
    }

    /**
     * Réactiver un comptable (état = 1)
     */
    public function reactiver(int $id): void
    {
        $this->userRepo->update($id, ['etat' => 1]);
    }

    /**
     * Suppression définitive (hard delete)
     */
    public function deleteComptable(int $id): void
    {
        $this->userRepo->forceDelete($id);
    }
}
