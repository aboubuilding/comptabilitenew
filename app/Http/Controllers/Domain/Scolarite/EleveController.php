<?php

namespace App\Http\Controllers\Admin\Scolarite;

use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Domain\Scolarite\Repositories\ClasseRepository;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Domain\Scolarite\Repositories\EleveRepository;
use App\Domain\Scolarite\Repositories\NationaliteRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\EleveRequest;
use App\Support\AnneeScolaireContext;
use App\Support\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * index() et archiver() sont pleinement fonctionnels. create()/edit()
 * rendent une vue (admin.scolarite.eleves.form) qui n'existe pas encore
 * — prochaine étape, avec le détail financier, l'impression et l'export
 * Excel (actions listées au cahier des charges mais hors scope de ce
 * tour, centré sur l'écran de liste).
 */
class EleveController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EleveRepository $eleves,
        private NationaliteRepository $nationalites,
        private CycleRepository $cycles,
        private NiveauRepository $niveaux,
        private ClasseRepository $classes,
        private AnneeRepository $annees,
        private AnneeScolaireContext $anneeContext,
        private FileUploadService $uploads,
    ) {
    }

    public function index(Request $request): View
    {
        $anneeId = (int) ($request->input('annee_id') ?: $this->anneeContext->id());

        return view('admin.scolarite.eleves.index', [
            'eleves' => $this->eleves->paginateWithFilters($request->only([
                'cycle_id', 'niveau_id', 'classe_id', 'statut', 'sexe', 'search',
            ]), $anneeId),
            'anneesOptions'  => $this->annees->allForSelector(),
            'cyclesOptions'  => $this->cycles->all(),
            'niveauxOptions' => $this->niveaux->all(),
            'classesOptions' => $this->classes->byAnnee($anneeId),
            'anneeId'        => $anneeId,
        ]);
    }

    public function create(): View
    {
        return view('admin.scolarite.eleves.form', [
            'eleve'               => null,
            'nationalitesOptions' => $this->nationalites->all(),
        ]);
    }

    public function store(EleveRequest $request): RedirectResponse
    {
        return $this->runWeb(function () use ($request) {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $data['photo'] = $this->uploads->store($request->file('photo'), 'eleves/photos');
            }
            if ($request->hasFile('certificat_medical')) {
                $data['certificat_medical'] = $this->uploads->store($request->file('certificat_medical'), 'eleves/certificats');
            }

            $this->eleves->create($data);
        }, 'scolarite.eleves.index', 'Fiche élève créée avec succès.');
    }

    public function edit(Eleve $eleve): View
    {
        return view('admin.scolarite.eleves.form', [
            'eleve'               => $eleve,
            'nationalitesOptions' => $this->nationalites->all(),
        ]);
    }

    public function update(EleveRequest $request, Eleve $eleve): RedirectResponse
    {
        return $this->runWeb(function () use ($request, $eleve) {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $this->uploads->delete($eleve->photo);
                $data['photo'] = $this->uploads->store($request->file('photo'), 'eleves/photos');
            }
            if ($request->hasFile('certificat_medical')) {
                $this->uploads->delete($eleve->certificat_medical);
                $data['certificat_medical'] = $this->uploads->store($request->file('certificat_medical'), 'eleves/certificats');
            }

            $this->eleves->update($eleve->id, $data);
        }, 'scolarite.eleves.index', 'Fiche élève modifiée avec succès.');
    }

    /**
     * "Archiver" (cahier des charges) = désactiver, pas supprimer —
     * BaseRepository::delete() fait déjà exactement ça (etat -> 0).
     */
    public function archiver(Eleve $eleve): RedirectResponse
    {
        if (! $this->eleves->canArchiver($eleve->id)) {
            return back()->with('error', 'Impossible d\'archiver cet élève.');
        }

        return $this->runWeb(function () use ($eleve) {
            $this->eleves->delete($eleve->id);
        }, 'scolarite.eleves.index', 'Fiche élève archivée avec succès.');
    }
}