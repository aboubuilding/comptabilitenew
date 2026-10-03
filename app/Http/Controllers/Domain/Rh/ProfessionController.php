<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\DTO\ProfessionData;
use App\Domain\Rh\Repositories\ProfessionRepository;
use App\Domain\Rh\Services\ProfessionService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StoreProfessionRequest;
use Illuminate\Http\Request;

class ProfessionController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ProfessionRepository $professions,
        private ProfessionService    $service,
    ) {
        $this->middleware('permission:employes.voir')->only(['index']);
        $this->middleware('permission:employes.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        return view('domain.rh.professions.index', [
            'professions' => $this->professions->listePaginee($filters),
            'filters'     => $filters,
        ]);
    }

    public function store(StoreProfessionRequest $request)
    {
        $data = ProfessionData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['profession' => $this->service->creer($data)],
            'Profession créée.'
        );
    }

    public function update(StoreProfessionRequest $request, int $profession)
    {
        $data = ProfessionData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['profession' => $this->service->modifier($profession, $data)],
            'Profession mise à jour.'
        );
    }

    public function destroy(int $profession)
    {
        return $this->runAjax(function () use ($profession) {
            $this->service->supprimer($profession);
            return null;
        }, 'Profession désactivée.');
    }
}
