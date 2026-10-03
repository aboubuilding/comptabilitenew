<?php
// app/Http/Controllers/Domain/Finances/RegleVentilationController.php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use App\Domain\Finances\Repositories\RegleVentilationRepository;
use App\Domain\Finances\Services\RegleVentilationService;
use App\Domain\Finances\Types\SensAnalytique;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegleVentilationController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private RegleVentilationRepository  $regles,
        private CentreAnalytiqueRepository  $centres,
        private RegleVentilationService     $service,
    ) {
        $this->middleware('permission:analytique.gerer');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['nature', 'sens']);

        return view('domain.finances.analytique.regles.index', [
            'regles'   => $this->regles->listePaginee($filters),
            'filters'  => $filters,
            'natures'  => $this->regles->naturesDistinctes(),
            'sens'     => SensAnalytique::options(),
            'centres'  => $this->centres->listePourSelect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nature'               => ['required', 'string', 'max:50'],
            'sens'                 => ['required', 'in:recette,depense'],
            'centre_analytique_id' => ['required', 'exists:centres_analytiques,id'],
            'pourcentage'          => ['required', 'numeric', 'min:0.01', 'max:100'],
            'priorite'             => ['nullable', 'integer', 'min:0'],
        ]);

        return $this->runAjax(
            fn () => ['regle' => $this->service->creer($data)],
            'Règle créée.'
        );
    }

    public function update(Request $request, int $regle)
    {
        $data = $request->validate([
            'nature'               => ['required', 'string', 'max:50'],
            'sens'                 => ['required', 'in:recette,depense'],
            'centre_analytique_id' => ['required', 'exists:centres_analytiques,id'],
            'pourcentage'          => ['required', 'numeric', 'min:0.01', 'max:100'],
            'priorite'             => ['nullable', 'integer', 'min:0'],
        ]);

        return $this->runAjax(
            fn () => ['regle' => $this->service->modifier($regle, $data)],
            'Règle mise à jour.'
        );
    }

    public function destroy(int $regle)
    {
        return $this->runAjax(function () use ($regle) {
            $this->service->supprimer($regle);
            return null;
        }, 'Règle désactivée.');
    }
}