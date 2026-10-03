<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\LigneData;
use App\Domain\ServicesEleves\Repositories\LigneRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreLigneRequest;

class LigneController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private LigneRepository $lignes,
    ) {
        $this->middleware('permission:transport.voir')->only(['index']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->only(['search', 'annee_id']);

        return view('domain.services-eleves.transport.lignes.index', [
            'lignes'  => $this->lignes->listePaginee($filters),
            'filters' => $filters,
        ]);
    }

    public function store(StoreLigneRequest $request)
    {
        $data = LigneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($data) {
            return ['ligne' => $this->lignes->create([
                'libelle'       => $data->libelle,
                'prix_minimal'  => $data->prixMinimal,
                'prix_plafond'  => $data->prixPlafond,
            ])];
        }, 'Ligne créée.');
    }

    public function update(StoreLigneRequest $request, int $ligne)
    {
        $data = LigneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($ligne, $data) {
            $this->lignes->update($ligne, [
                'libelle'       => $data->libelle,
                'prix_minimal'  => $data->prixMinimal,
                'prix_plafond'  => $data->prixPlafond,
            ]);
            return null;
        }, 'Ligne mise à jour.');
    }

    public function destroy(int $ligne)
    {
        return $this->runAjax(function () use ($ligne) {
            $this->lignes->delete($ligne);
            return null;
        }, 'Ligne désactivée.');
    }
}