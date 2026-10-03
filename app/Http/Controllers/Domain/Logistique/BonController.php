<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\BonRepository;
use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Logistique\Services\BonService;
use App\Domain\Logistique\Types\TypeBon;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreBonRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BonController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private BonRepository     $bons,
        private MagasinRepository $magasins,
        private ProduitRepository $produits,
        private BonService        $service,
    ) {
        $this->middleware('permission:stock.voir')->only(['index', 'show']);
        $this->middleware('permission:stock.gerer')->only(['store', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'type', 'magasin_id', 'search', 'date_debut', 'date_fin']);

        return view('domain.logistique.bons.index', [
            'bons'      => $this->bons->listePaginee($filters),
            'filters'   => $filters,
            'types'     => TypeBon::options(),
            'magasins'  => $this->magasins->listePourSelect(),
            'produits'  => $this->produits->listePourSelect(),
            'compteurs' => $this->bons->compterParType(),
        ]);
    }

    public function show(int $bon)
    {
        return view('domain.logistique.bons.show', [
            'bon' => $this->bons->findAvecDetails($bon),
        ]);
    }

    public function store(StoreBonRequest $request)
    {
        return $this->runAjax(
            fn () => ['bon' => $this->service->creer($request->validated(), Auth::id())],
            'Bon créé et stock mis à jour.'
        );
    }
}