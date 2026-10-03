<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\Models\Deduction;
use App\Domain\Rh\Models\Remuneration;
use App\Domain\Rh\Models\Retenue;
use App\Domain\Rh\Repositories\EmployeRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StoreDeductionRequest;
use App\Http\Requests\Domain\Rh\StoreRemunerationRequest;
use App\Http\Requests\Domain\Rh\StoreRetenueRequest;
use Illuminate\Http\Request;

class RemunerationController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EmployeRepository $employes,
    ) {
        $this->middleware('permission:paie.voir')->only(['index']);
        $this->middleware('permission:paie.gerer')->only(['storeRemuneration', 'storeDeduction', 'storeRetenue']);
    }

    public function index(Request $request)
    {
        $employeId = $request->input('employe_id');

        $employe = $employeId ? $this->employes->findOrFail((int) $employeId) : null;

        return view('domain.rh.remunerations.index', [
            'employes'     => $this->employes->listePourSelect(),
            'employe'      => $employe,
            'remuneration' => $employe
                ? Remuneration::where('employe_id', $employe->id)->where('etat', 1)->first()
                : null,
            'deduction'    => $employe
                ? Deduction::where('personnel_id', $employe->id)->where('etat', 1)->first()
                : null,
            'retenue'      => $employe
                ? Retenue::where('employe_id', $employe->id)->where('etat', 1)->first()
                : null,
        ]);
    }

    public function storeRemuneration(StoreRemunerationRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($data) {
            // Upsert : une seule rémunération active par employé
            $remuneration = Remuneration::updateOrCreate(
                ['employe_id' => $data['employe_id'], 'etat' => 1],
                [
                    'salaire_base'    => $data['salaire_base'],
                    'prime_anciennete'=> $data['prime_anciennete'] ?? 0,
                    'sursalaire'      => $data['sursalaire'] ?? 0,
                    'bonus'           => $data['bonus'] ?? 0,
                    'logement'        => $data['logement'] ?? 0,
                    'nourriture'      => $data['nourriture'] ?? 0,
                    'deplacement'     => $data['deplacement'] ?? 0,
                    'autre_avantage'  => $data['autre_avantage'] ?? 0,
                ]
            );

            return ['remuneration' => $remuneration];
        }, 'Rémunération enregistrée.');
    }

    public function storeDeduction(StoreDeductionRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($data) {
            $deduction = Deduction::updateOrCreate(
                ['personnel_id' => $data['employe_id'], 'etat' => 1],
                [
                    'cnss'                 => $data['cnss'] ?? 0,
                    'charges_familiale'    => $data['charges_familiale'] ?? 0,
                    'forfait_professionnel'=> $data['forfait_professionnel'] ?? 0,
                ]
            );

            return ['deduction' => $deduction];
        }, 'Déductions enregistrées.');
    }

    public function storeRetenue(StoreRetenueRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($data) {
            $retenue = Retenue::updateOrCreate(
                ['employe_id' => $data['employe_id'], 'etat' => 1],
                ['irpp' => $data['irpp']]
            );

            return ['retenue' => $retenue];
        }, 'Retenues IRPP enregistrées.');
    }
}