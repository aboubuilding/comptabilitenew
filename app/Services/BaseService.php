<?php

namespace App\Services;

use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

abstract class BaseService
{
    protected BaseRepository $repo;
    protected int $perPage = 15;
    protected string $entityName = 'élément';
    protected array $defaultSelectFields = ['id', 'libelle', 'etat', 'created_at'];
    protected array $listRelations = [];
    protected array $listAppends = [];

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }

    // ─────────────────────────────────────────────────────────────
    //  Méthodes de liste (retournent déjà des tableaux)
    // ─────────────────────────────────────────────────────────────

    public function all(): array
    {
        $items = $this->repo->activeQuery()
            ->select($this->defaultSelectFields)
            ->with($this->listRelations)
            ->get();
        return $this->formatCollection($items);
    }

    public function list(Request $request): array
    {
        $query = $this->buildListQuery($request);
        $items = $query->get();
        return $this->formatCollection($items);
    }

    public function paginate(Request $request): array
    {
        $perPage = $request->integer('per_page', $this->perPage);
        $query = $this->buildListQuery($request);
        $paginator = $query->paginate($perPage);

        return [
            'data' => $this->formatCollection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Hooks (surchargeables)
    // ─────────────────────────────────────────────────────────────

    protected function buildListQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->repo->activeQuery()
            ->select($this->defaultSelectFields)
            ->with($this->listRelations);

        if ($request->filled('search')) {
            $query->where('libelle', 'like', "%{$request->search}%");
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->integer('etat'));
        }
        if ($request->filled('annee_id')) {
            $query->where('annee_id', $request->integer('annee_id'));
        }
        if ($request->filled('cycle_id')) {
            $query->where('cycle_id', $request->integer('cycle_id'));
        }
        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->integer('niveau_id'));
        }

        $sortField = $request->get('sort_by', 'created_at');
        if (in_array($sortField, $this->defaultSelectFields)) {
            $sortDir = in_array(strtolower($request->get('sort_dir', 'desc')), ['asc', 'desc'])
                ? $request->get('sort_dir')
                : 'desc';
            $query->orderBy($sortField, $sortDir);
        }
        return $query;
    }

    protected function formatListItem($item): array
    {
        if (!empty($this->listAppends)) {
            $item->append($this->listAppends);
        }
        return $item->toArray();
    }

    protected function formatCollection(Collection $items): array
    {
        return $items->map(fn($item) => $this->formatListItem($item))->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    //  CRUD avec retours standardisés (array)
    // ─────────────────────────────────────────────────────────────

    public function show(int $id): array
    {
        try {
            $item = $this->repo->findOrFail($id);
            return $this->formatResponse(true, null, $item);
        } catch (\Exception $e) {
            return $this->formatResponse(false, "{$this->entityName} introuvable");
        }
    }

    public function store(array $validatedData): array
    {
        try {
            $this->checkUniqueness($validatedData, 'libelle');
            $item = $this->repo->create($validatedData);
            return $this->formatResponse(true, "{$this->entityName} créé avec succès", $item);
        } catch (ValidationException $e) {
            return $this->formatResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            return $this->formatResponse(false, "Erreur : " . $e->getMessage());
        }
    }

    public function update(int $id, array $validatedData): array
    {
        try {
            $this->checkUniqueness($validatedData, 'libelle', $id);
            $this->repo->update($id, $validatedData);
            $item = $this->repo->find($id);
            return $this->formatResponse(true, "{$this->entityName} mis à jour", $item);
        } catch (ValidationException $e) {
            return $this->formatResponse(false, $e->getMessage());
        } catch (\Exception $e) {
            return $this->formatResponse(false, "{$this->entityName} introuvable ou erreur");
        }
    }

    public function destroy(int $id): array
    {
        try {
            $this->repo->delete($id);
            return $this->formatResponse(true, "{$this->entityName} supprimé");
        } catch (\Exception $e) {
            return $this->formatResponse(false, "Impossible de supprimer");
        }
    }

    public function restore(int $id): array
    {
        try {
            $this->repo->restore($id);
            return $this->formatResponse(true, "{$this->entityName} restauré");
        } catch (\Exception $e) {
            return $this->formatResponse(false, "Impossible de restaurer");
        }
    }

    public function getForSelect(array $filters = [], string $labelField = 'libelle', string $valueField = 'id'): array
    {
        $query = $this->repo->activeQuery()->select($valueField, $labelField);
        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }
        $items = $query->orderBy($labelField)->get()
            ->map(fn($item) => ['value' => $item->$valueField, 'label' => $item->$labelField]);
        return $this->formatResponse(true, '', $items);
    }

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

    protected function formatResponse(bool $success, string $message = '', mixed $data = null, array $meta = []): array
    {
        $response = ['success' => $success];
        if ($message) $response['message'] = $message;
        if ($data !== null) $response['data'] = $data;
        if (!empty($meta)) $response['meta'] = $meta;
        return $response;
    }
}
