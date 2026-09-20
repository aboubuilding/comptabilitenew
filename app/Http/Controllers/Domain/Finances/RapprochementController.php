<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\Imports\ReleveBancaireImport;
use App\Domain\Finances\Repositories\BanqueRepository;
use App\Domain\Finances\Services\RapprochementBancaireService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RapprochementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private BanqueRepository            $banques,
        private RapprochementBancaireService $service,
    ) {
        $this->middleware('permission:cheques.rapprocher');
    }

    /** Formulaire d'import. */
    public function index(int $banque)
    {
        $banque = $this->banques->findOrFail($banque);

        return view('domain.finances.banques.rapprochement', [
            'banque' => $banque,
        ]);
    }

    /**
     * Étape 1 : upload + analyse. On stocke le résultat en session pour
     * l'étape 2 (validation), afin de ne pas redemander le fichier.
     */
    public function analyser(Request $request, int $banque)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $banqueModel = $this->banques->findOrFail($banque);

        $import = new ReleveBancaireImport();
        Excel::import($import, $request->file('fichier'));

        if (empty($import->lignes)) {
            return back()->with('error', "Aucune ligne exploitable dans le fichier (vérifiez les en-têtes : numero, date, montant).");
        }

        $analyse = $this->service->analyser($banqueModel->id, $import->lignes);

        // On met en session ce qui sera nécessaire à l'étape 2.
        session()->put("rapprochement.{$banqueModel->id}", [
            'matched'   => collect($analyse['matched'])->map(fn ($m) => [
                'cheque_id' => $m['cheque']->id,
                'numero'    => $m['ligne']->numero,
                'montant'   => $m['ligne']->montant,
                'date'      => $m['ligne']->dateEncaissement,
            ])->all(),
            'importees' => count($import->lignes),
            'ignorees'  => $import->ignorees,
        ]);

        return view('domain.finances.banques.rapprochement-preview', [
            'banque'     => $banqueModel,
            'analyse'    => $analyse,
            'lignesTotal' => count($import->lignes),
            'lignesIgnorees' => $import->ignorees,
        ]);
    }

    /** Étape 2 : validation et application en masse. */
    public function appliquer(Request $request, int $banque)
    {
        $request->validate([
            'paires'                  => ['required', 'array'],
            'paires.*.cheque_id'      => ['required', 'integer', 'exists:cheques,id'],
            'paires.*.date'           => ['required', 'date'],
        ]);

        return $this->runWeb(
            function () use ($request, $banque) {
                $result = $this->service->appliquerEnMasse(
                    $request->input('paires'),
                    Auth::id()
                );

                if (! empty($result['errors'])) {
                    session()->flash('warning', implode(' | ', $result['errors']));
                }

                session()->flash('success', "{$result['ok']} chèque(s) rapproché(s) avec succès.");
            },
            'finances.banques.show',
            'Rapprochement bancaire terminé.',
            ['banque' => $banque]
        );
    }
}