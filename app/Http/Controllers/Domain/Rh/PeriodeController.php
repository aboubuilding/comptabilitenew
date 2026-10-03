<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\Repositories\PeriodeRepository;
use App\Domain\Rh\Services\PaieService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StorePeriodeRequest;
use App\Jobs\GenererBulletinsPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PeriodeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PeriodeRepository $periodes,
        private PaieService       $paieService,
    ) {
        $this->middleware('permission:paie.voir')->only(['index']);
        $this->middleware('permission:paie.gerer')->only([
            'store', 'update', 'destroy', 'genererBulletins',
        ]);
        $this->middleware('permission:paie.voir')->only(['statutGeneration']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'annee_id']);

        return view('domain.rh.periodes.index', [
            'periodes' => $this->periodes->listePaginee($filters),
            'filters'  => $filters,
        ]);
    }

    public function store(StorePeriodeRequest $request)
    {
        return $this->runAjax(
            fn () => ['periode' => $this->periodes->create($request->validated())],
            'Période créée.'
        );
    }

    public function update(StorePeriodeRequest $request, int $periode)
    {
        return $this->runAjax(function () use ($request, $periode) {
            $this->periodes->update($periode, $request->validated());
            return null;
        }, 'Période mise à jour.');
    }

    public function destroy(int $periode)
    {
        return $this->runAjax(function () use ($periode) {
            $this->periodes->delete($periode);
            return null;
        }, 'Période désactivée.');
    }

    /**
     * Dispatch le job de génération en masse (asynchrone).
     * Le job écrit dans le cache au fur et à mesure de sa progression.
     * Le front fait ensuite du polling sur statutGeneration().
     */
    public function genererBulletins(int $periode)
    {
        return $this->runAjax(function () use ($periode) {
            // Réinitialise le cache AVANT de dispatcher le job.
            Cache::put("paie.statut.{$periode}", 'en_cours', now()->addHours(24));
            Cache::forget("paie.recap.{$periode}");
            Cache::forget("paie.erreur.{$periode}");

            // Dispatch du job queue
            GenererBulletinsPeriode::dispatch($periode, Auth::id());

            return [
                'statut'  => 'en_cours',
                'periode' => $periode,
            ];
        }, 'Génération lancée. Suivi en cours...');
    }

    /**
     * Endpoint de polling : renvoie le statut et, si terminé, le récapitulatif.
     * Appelé en boucle par le front après le dispatch.
     */
    public function statutGeneration(int $periode)
    {
        $statut = Cache::get("paie.statut.{$periode}", 'jamais_lance');
        $recap  = Cache::get("paie.recap.{$periode}");
        $erreur = Cache::get("paie.erreur.{$periode}");

        return response()->json([
            'statut' => $statut,
            'recap'  => $recap,
            'erreur' => $erreur,
        ]);
    }
}