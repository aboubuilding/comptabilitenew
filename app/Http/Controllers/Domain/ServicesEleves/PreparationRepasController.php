<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\ServicesEleves\DTO\PreparationRepasData;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Domain\ServicesEleves\Repositories\PreparationRepasRepository;
use App\Domain\ServicesEleves\Services\PreparationRepasService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StorePreparationRepasRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PreparationRepasController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PreparationRepasRepository $preparations,
        private MenuRepository             $menus,
        private MagasinRepository          $magasins,
        private PreparationRepasService    $service,
    ) {
        $this->middleware('permission:cantine.voir')->only(['index', 'export']);
        $this->middleware('permission:cantine.gerer')->only(['store']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'menu_id', 'date_debut', 'date_fin']);
        $anneeId = app(AnneeScolaireContext::class)->id();

        return view('domain.services-eleves.cantine.preparations.index', [
            'preparations' => $this->preparations->listePaginee($filters),
            'filters'      => $filters,
            'menus'        => $this->menus->activeQuery()->orderByDesc('date_service')->limit(100)->get(['id', 'libelle', 'date_service']),
            'magasins'     => $this->magasins->listePourSelect(),
            'stats'        => $this->preparations->stats($anneeId, $filters['date_debut'] ?? null, $filters['date_fin'] ?? null),
        ]);
    }

    public function store(StorePreparationRepasRequest $request)
    {
        $data = PreparationRepasData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['preparation' => $this->service->enregistrer($data, Auth::id())],
            'Préparation enregistrée. Stock mis à jour.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'menu_id', 'date_debut', 'date_fin']);
        $preparations = $this->preparations->listePaginee($filters, perPage: 10000);

        $filename = 'preparations_repas_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($preparations) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Date', 'Menu', 'Magasin', 'Parts', 'Coût réel',
                'Coût moyen/part', 'Responsable',
            ], ';');

            foreach ($preparations as $p) {
                $coutMoyen = $p->nombre_parts > 0 ? round((float) $p->cout_reel / $p->nombre_parts, 2) : 0;
                fputcsv($out, [
                    optional($p->date_preparation)->format('d/m/Y'),
                    $p->menu?->libelle,
                    $p->magasin?->libelle,
                    $p->nombre_parts,
                    number_format((float) $p->cout_reel, 2, ',', ' '),
                    number_format($coutMoyen, 2, ',', ' '),
                    $p->responsable?->name,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}