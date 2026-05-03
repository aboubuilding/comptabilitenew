<?php

namespace App\Services;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

abstract class BaseService
{
    /**
     * Repository injecté
     */
    protected BaseRepository $repo;

    /**
     * Pagination par défaut
     */
    protected int $perPage = 15;

    /**
     * Nom de l'entité pour les messages (ex: "Cycle", "Niveau")
     */
    protected string $entityName = 'élément';

    /**
     * Champs par défaut pour les listes
     */
    protected array $defaultSelectFields = ['id', 'libelle', 'etat', 'created_at'];

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }

    // ─────────────────────────────────────────────────────────────
    // 📋 LISTE & PAGINATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste paginée avec filtres dynamiques (search, etat, annee_id, etc.)
     */
    public function index(Request $request): array
    {
        $perPage = $request->integer('per_page', $this->perPage);
        $query = $this->repo->activeQuery()->select($this->defaultSelectFields);

        // 🔍 Recherche globale
        if ($request->filled('search')) {
            $query->where('libelle', 'like', "%{$request->search}%");
        }
        // 🎛️ Filtres standards
        if ($request->filled('etat')) {
            $query->where('etat', $request->integer('etat'));
        }
        if ($request->filled('annee_id')) {
            $query->where('annee_id', $request->integer('annee_id'));
        }
        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', $request->integer('cycle_id'));
        }

        // 📊 Tri
        $sortField = in_array($request->get('sort_by'), $this->defaultSelectFields) 
            ? $request->get('sort_by') 
            : 'created_at';
        $sortDir = in_array(strtolower($request->get('sort_dir')), ['asc', 'desc']) 
            ? $request->get('sort_dir') 
            : 'desc';
            
        $query->orderBy($sortField, $sortDir);

        $paginator = $query->paginate($perPage);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 🔍 DÉTAIL & LISTES RAPIDES
    // ─────────────────────────────────────────────────────────────

    /**
     * Détail d'un élément
     */
    public function show(int $id): object
    {
        return $this->repo->findOrFail($id);
    }

    /**
     * 📊 Liste simplifiée pour selects/dropdowns (API ou Blade)
     */
    public function getForSelect(array $filters = []): Collection
    {
        $query = $this->repo->activeQuery()->select('id', 'libelle', 'etat');

        if (!empty($filters['annee_id'])) {
            $query->where('annee_id', $filters['annee_id']);
        }
        if (!empty($filters['cycle_id'])) {
            $query->where('cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('niveau_id', $filters['niveau_id']);
        }

        return $query->orderBy('libelle')->get();
    }

    // ─────────────────────────────────────────────────────────────
    // ➕ CRÉATION / ✏️ MISE À JOUR / 🗑️ SUPPRESSION
    // ─────────────────────────────────────────────────────────────

    /**
     * Création d'un élément
     */
    public function store(array $validatedData): object
    {
        $this->checkUniqueness($validatedData, 'libelle');
        return $this->repo->create($validatedData);
    }

    /**
     * Mise à jour d'un élément
     */
    public function update(int $id, array $validatedData): object
    {
        $this->checkUniqueness($validatedData, 'libelle', $id);
        $this->repo->update($id, $validatedData);
        return $this->repo->find($id);
    }

    /**
     * Suppression logique
     */
    public function destroy(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * ♻️ Restauration
     */
    public function restore(int $id): bool
    {
        return $this->repo->restore($id);
    }

    // ─────────────────────────────────────────────────────────────
    // 🔧 HELPERS INTERNES
    // ─────────────────────────────────────────────────────────────

    /**
     * Vérifie l'unicité du libelle avant création/mise à jour
     */
    protected function checkUniqueness(array $data, string $field = 'libelle', ?int $excludeId = null): void
    {
        if (empty($data[$field])) return;

        $query = $this->repo->activeQuery()->where($field, $data[$field]);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                $field => "Le {$this->entityName} '{$data[$field]}' existe déjà."
            ]);
        }
    }

    /**
     * 📦 Formateur de réponse standardisée (utilisé dans les contrôleurs)
     */
    public function formatResponse(bool $success, string $message = '', mixed $data = null, array $meta = []): array
    {
        return array_filter([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
            'meta'    => $meta,
        ], fn($v) => $v !== null);
    }
}