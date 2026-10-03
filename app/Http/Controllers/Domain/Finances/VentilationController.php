<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\ImputationData;
use App\Domain\Finances\Repositories\DepenseRepository;
use App\Domain\Finances\Services\VentilationAnalytiqueService;
use App\Domain\Finances\Types\SensAnalytique;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VentilationController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private VentilationAnalytiqueService $service,
        private DepenseRepository            $depenses,
    ) {
        $this->middleware('permission:analytique.ventiler');
    }

    /** Ventile une dépense sur un ou plusieurs centres analytiques. */
    public function ventilerDepense(Request $request, int $depense)
    {
        $data = $request->validate([
            'sens'                               => ['required', 'string'],
            'imputations'                        => ['required', 'array', 'min:1'],
            'imputations.*.centre_analytique_id' => ['required', 'exists:centres_analytiques,id'],
            'imputations.*.montant'              => ['nullable', 'numeric', 'min:0'],
            'imputations.*.pourcentage'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        return $this->runAjax(function () use ($depense, $data) {
            $depenseModel = $this->depenses->findOrFail($depense);

            $imputations = array_map(
                fn (array $row) => ImputationData::fromArray($row),
                $data['imputations']
            );

            $this->service->ventiler(
                source: $depenseModel,
                imputations: $imputations,
                sens: $data['sens'],
                montantSource: (float) $depenseModel->montant,
                userId: Auth::id(),
            );

            return null;
        }, 'Ventilation enregistrée.');
    }
}