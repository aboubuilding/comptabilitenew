<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Types\TypeMagasin;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreMagasinRequest;
use Illuminate\Http\Request;

class MagasinController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private MagasinRepository $magasins,
    ) {
        $this->middleware('permission:stock.voir')->only(['index', 'show']);
        $this->middleware('permission:stock.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type']);

        return view('domain.logistique.magasins.index', [
            'magasins' => $this->magasins->listePaginee($filters),
            'filters'  => $filters,
            'types'    => TypeMagasin::options(),
        ]);
    }

    public function show(int $magasin)
    {
        return view('domain.logistique.magasins.show', [
            'magasin' => $this->magasins->findAvecStocks($magasin),
        ]);
    }

    public function store(StoreMagasinRequest $request)
    {
        return $this->runAjax(
            fn () => ['magasin' => $this->magasins->create($request->validated())],
            'Magasin créé.'
        );
    }

    public function update(StoreMagasinRequest $request, int $magasin)
    {
        $this->magasins->update($magasin, $request->validated());
        return $this->runAjax(fn () => null, 'Magasin mis à jour.');
    }

    public function destroy(int $magasin)
    {
        return $this->runAjax(function () use ($magasin) {
            $this->magasins->delete($magasin);
            return null;
        }, 'Magasin désactivé.');
    }
}