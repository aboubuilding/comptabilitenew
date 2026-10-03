<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\ServicesEleves\DTO\MenuData;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Domain\ServicesEleves\Services\MenuService;
use App\Domain\ServicesEleves\Types\TypeRepas;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreMenuRequest;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private MenuRepository    $menus,
        private MagasinRepository $magasins,
        private ProduitRepository $produits,
        private MenuService       $service,
    ) {
        $this->middleware('permission:cantine.voir')->only(['index', 'show']);
        $this->middleware('permission:cantine.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'type_repas', 'search', 'date_debut', 'date_fin']);

        return view('domain.services-eleves.cantine.menus.index', [
            'menus'     => $this->menus->listePaginee($filters),
            'filters'   => $filters,
            'types'     => TypeRepas::options(),
            'magasins'  => $this->magasins->listePourSelect(),
            'produits'  => $this->produits->listePourSelect(),
        ]);
    }

    public function show(int $menu)
    {
        return response()->json([
            'menu' => $this->menus->findAvecProduits($menu),
        ]);
    }

    public function store(StoreMenuRequest $request)
    {
        $data = MenuData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['menu' => $this->service->creer($data)],
            'Menu créé.'
        );
    }

    public function update(StoreMenuRequest $request, int $menu)
    {
        $data = MenuData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['menu' => $this->service->modifier($menu, $data)],
            'Menu mis à jour.'
        );
    }

    public function destroy(int $menu)
    {
        return $this->runAjax(function () use ($menu) {
            $this->service->supprimer($menu);
            return null;
        }, 'Menu supprimé.');
    }
}