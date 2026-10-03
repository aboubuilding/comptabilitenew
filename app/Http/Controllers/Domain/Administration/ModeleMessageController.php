<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Domain\Administration\Models\ModeleMessage;
use App\Domain\Administration\Repositories\ModeleMessageRepository;
use App\Domain\Administration\Services\ModeleMessageService;
use App\Domain\Administration\Services\MessageService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Administration\StoreModeleMessageRequest;
use Illuminate\Http\Request;

/**
 * CRUD des modèles de messages.
 */
class ModeleMessageController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function __construct(
        private readonly ModeleMessageService $service,
        private readonly ModeleMessageRepository $repository
    ) {
        $this->middleware('permission:modeles.gerer')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filtres = $request->only(['recherche']);
        $modeles = $this->repository->listePaginee($filtres, 15);

        return view('domain.administration.modeles.index', compact('modeles', 'filtres'));
    }

    public function create()
    {
        return view('domain.administration.modeles._modal_form', [
            'modele' => null,
            'action' => route('administration.modeles.store'),
            'method' => 'POST',
            'titre'  => 'Nouveau modèle',
        ]);
    }

    public function store(StoreModeleMessageRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $modele = $this->service->creer($request->validated());
                $this->logAction('create', 'ModeleMessage', $modele->id, "Création du modèle « {$modele->libelle} »");
            },
            'Modèle créé avec succès.'
        );
    }

    public function edit(ModeleMessage $modele)
    {
        return view('domain.administration.modeles._modal_form', [
            'modele' => $modele,
            'action' => route('administration.modeles.update', $modele),
            'method' => 'PUT',
            'titre'  => 'Modifier le modèle',
        ]);
    }

    public function update(StoreModeleMessageRequest $request, ModeleMessage $modele)
    {
        return $this->runAjax(
            function () use ($request, $modele) {
                $this->service->mettreAJour($modele, $request->validated());
                $this->logAction('update', 'ModeleMessage', $modele->id, "Modification du modèle « {$modele->libelle} »");
            },
            'Modèle mis à jour avec succès.'
        );
    }

    public function destroy(ModeleMessage $modele)
    {
        return $this->runAjax(
            function () use ($modele) {
                $libelle = $modele->libelle;
                $this->service->supprimer($modele);
                $this->logAction('delete', 'ModeleMessage', $modele->id, "Suppression du modèle « {$libelle} »");
            },
            'Modèle supprimé avec succès.'
        );
    }
}