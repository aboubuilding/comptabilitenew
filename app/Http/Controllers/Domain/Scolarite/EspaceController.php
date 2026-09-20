<?php

namespace App\Http\Controllers\Admin\Scolarite;

use App\Domain\Scolarite\Models\Espace;
use App\Domain\Scolarite\Repositories\EspaceRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\EspaceRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * index() et le CRUD minimal de l'espace (nom_famille) sont pleinement
 * fonctionnels — ajout/modification via modale, même pattern que Cycle.
 * La gestion détaillée d'un espace (parents rattachés, fratrie, compte
 * portail, désignation du parent principal) nécessite un écran de détail
 * dédié, pas encore construit — prochaine étape.
 */
class EspaceController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EspaceRepository $espaces,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    public function index(Request $request): View
    {
        $anneeId = (int) ($request->input('annee_id') ?: $this->anneeContext->id());

        return view('admin.scolarite.espaces.index', [
            'espaces' => $this->espaces->paginateWithFilters($request->only(['search']), $anneeId),
            'anneeId' => $anneeId,
        ]);
    }

    public function edit(Espace $espace): JsonResponse
    {
        return response()->json(['espace' => $espace]);
    }

    public function store(EspaceRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $espace = $this->espaces->create(array_merge(
                $request->validated(),
                ['annee_id' => $this->anneeContext->id()]
            ));

            return ['espace' => $espace];
        }, 'Espace familial créé avec succès.');
    }

    public function update(EspaceRequest $request, Espace $espace): JsonResponse
    {
        return $this->runAjax(function () use ($request, $espace) {
            $this->espaces->update($espace->id, $request->validated());
        }, 'Espace familial modifié avec succès.');
    }

    public function archiver(Espace $espace): RedirectResponse
    {
        return $this->runWeb(function () use ($espace) {
            $this->espaces->delete($espace->id);
        }, 'scolarite.espaces.index', 'Espace familial archivé avec succès.');
    }
}