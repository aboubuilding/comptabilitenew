<?php

namespace App\Http\Controllers\Domain\Scolarite;

use App\Domain\Scolarite\DTO\CompteDTO;
use App\Domain\Scolarite\DTO\EspaceDTO;
use App\Domain\Scolarite\DTO\ParentDTO;
use App\Domain\Scolarite\Exports\EspacesExport;
use App\Domain\Scolarite\Models\Compte;
use App\Domain\Scolarite\Models\Espace;
use App\Domain\Scolarite\Models\Nationalite;
use App\Domain\Scolarite\Models\ParentEleve;
use App\Domain\Scolarite\Repositories\CompteRepository;
use App\Domain\Scolarite\Repositories\EspaceRepository;
use App\Domain\Scolarite\Repositories\ParentEleveRepository;
use App\Domain\Scolarite\Services\CompteService;
use App\Domain\Scolarite\Services\EspaceService;
use App\Domain\Scolarite\Services\ParentEleveService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Scolarite\RegrouperFratrieRequest;
use App\Http\Requests\Domain\Scolarite\StoreCompteRequest;
use App\Http\Requests\Domain\Scolarite\StoreEspaceRequest;
use App\Http\Requests\Domain\Scolarite\StoreParentRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Module 1.2 — Parents & Familles (espaces familiaux).
 */
class EspaceController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function __construct(
        private readonly EspaceService $espaceService,
        private readonly ParentEleveService $parentService,
        private readonly CompteService $compteService,
        private readonly EspaceRepository $espaceRepository,
        private readonly ParentEleveRepository $parentRepository,
        private readonly CompteRepository $compteRepository,
        private readonly AnneeScolaireContext $anneeContext
    ) {
        $this->middleware('permission:familles.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:familles.gerer')->except(['index', 'show', 'export']);
    }

    // ============================================================
    // ESPACES
    // ============================================================

    public function index(Request $request)
    {
        $filtres = $request->only(['recherche', 'avec_compte']);
        $espaces = $this->espaceRepository->listePaginee($filtres, 15);
        $anneeId = $this->anneeContext->id();

        return view('domain.scolarite.espaces.index', compact('espaces', 'filtres', 'anneeId'));
    }

    public function show(Espace $espace)
    {
        $espace->load([
            'parents' => fn ($q) => $q->where('etat', 1)->orderByDesc('is_principal'),
            'eleves'  => function ($q) {
                $q->where('etat', 1)->with(['nationalite', 'inscriptionActive.classe', 'inscriptionActive.niveau']);
            },
            'comptes' => fn ($q) => $q->where('etat', 1)->with('parent'),
        ]);

        return view('domain.scolarite.espaces.show', compact('espace'));
    }

    public function create()
    {
        return view('domain.scolarite.espaces._modal_form', [
            'espace' => null,
            'action' => route('scolarite.espaces.store'),
            'method' => 'POST',
            'titre'  => 'Nouvel espace familial',
        ]);
    }

    public function store(StoreEspaceRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto = EspaceDTO::fromArray($request->validated());
                $espace = $this->espaceService->creer($dto);

                $this->logAction('create', 'Espace', $espace->id,
                    "Création de l'espace familial « {$espace->nom_famille} »");

                return $espace;
            },
            fn (Espace $e) => "Espace familial « {$e->nom_famille} » créé avec succès."
        );
    }

    public function edit(Espace $espace)
    {
        return view('domain.scolarite.espaces._modal_form', [
            'espace' => $espace,
            'action' => route('scolarite.espaces.update', $espace),
            'method' => 'PUT',
            'titre'  => "Modifier l'espace familial",
        ]);
    }

    public function update(StoreEspaceRequest $request, Espace $espace)
    {
        return $this->runAjax(
            function () use ($request, $espace) {
                $dto = EspaceDTO::fromArray($request->validated());
                $this->espaceService->mettreAJour($espace, $dto);

                $this->logAction('update', 'Espace', $espace->id,
                    "Modification de l'espace « {$espace->nom_famille} »");

                return $espace;
            },
            fn (Espace $e) => "Espace familial mis à jour."
        );
    }

    public function destroy(Espace $espace)
    {
        return $this->runAjax(
            function () use ($espace) {
                $nom = $espace->nom_famille;
                $this->espaceService->supprimer($espace);
                $this->logAction('delete', 'Espace', $espace->id,
                    "Suppression de l'espace « {$nom} »");
            },
            'Espace familial supprimé.'
        );
    }

    /**
     * Archivage logique (route scolarite.espaces.archiver) : l'espace passe
     * à l'état inactif, aucune donnée n'est supprimée.
     */
    public function archiver(Espace $espace)
    {
        return $this->runAjax(
            function () use ($espace) {
                $this->espaceService->supprimer($espace);
                $this->logAction('archive', 'Espace', $espace->id,
                    "Archivage de l'espace « {$espace->nom_famille} »");
            },
            'Espace familial archivé.'
        );
    }

    public function export(Request $request)
    {
        $filtres = $request->only(['recherche']);
        $espaces = $this->espaceRepository->listePourExport($filtres);

        $this->logAction('export', 'Espace', null, 'Export Excel des espaces familiaux', $filtres);

        return Excel::download(
            new EspacesExport($espaces),
            'espaces_familiaux_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // ============================================================
    // PARENTS
    // ============================================================

    public function parentCreate(Espace $espace)
    {
        $nationalites = Nationalite::actif()->orderBy('libelle')->get();

        return view('domain.scolarite.espaces._modal_parent', [
            'espace'       => $espace,
            'parent'       => null,
            'nationalites' => $nationalites,
            'action'       => route('scolarite.espaces.parents.store', $espace),
            'method'       => 'POST',
            'titre'        => 'Nouveau parent / tuteur',
        ]);
    }

    public function parentStore(StoreParentRequest $request, Espace $espace)
    {
        return $this->runAjax(
            function () use ($request, $espace) {
                $data = $request->validated();
                $data['espace_id'] = $espace->id;

                $dto = ParentDTO::fromArray($data);
                $parent = $this->parentService->creer($dto);

                $this->logAction('create', 'ParentEleve', $parent->id,
                    "Ajout du parent « {$parent->nom_complet} » à l'espace « {$espace->nom_famille} »");

                return $parent;
            },
            fn (ParentEleve $p) => "Parent « {$p->nom_complet} » ajouté."
        );
    }

    public function parentEdit(Espace $espace, ParentEleve $parent)
    {
        $nationalites = Nationalite::actif()->orderBy('libelle')->get();

        return view('domain.scolarite.espaces._modal_parent', [
            'espace'       => $espace,
            'parent'       => $parent,
            'nationalites' => $nationalites,
            'action'       => route('scolarite.espaces.parents.update', [$espace, $parent]),
            'method'       => 'PUT',
            'titre'        => 'Modifier le parent / tuteur',
        ]);
    }

    public function parentUpdate(StoreParentRequest $request, Espace $espace, ParentEleve $parent)
    {
        return $this->runAjax(
            function () use ($request, $espace, $parent) {
                $data = $request->validated();
                $data['espace_id'] = $espace->id;

                $dto = ParentDTO::fromArray($data);
                $this->parentService->mettreAJour($parent, $dto);

                $this->logAction('update', 'ParentEleve', $parent->id,
                    "Modification du parent « {$parent->nom_complet} »");

                return $parent;
            },
            fn (ParentEleve $p) => "Parent « {$p->nom_complet} » mis à jour."
        );
    }

    public function parentDesignerPrincipal(Espace $espace, ParentEleve $parent)
    {
        return $this->runAjax(
            function () use ($espace, $parent) {
                $this->parentService->designerPrincipal($parent);

                $this->logAction('update', 'ParentEleve', $parent->id,
                    "Désignation de « {$parent->nom_complet} » comme parent principal de « {$espace->nom_famille} »");

                return $parent;
            },
            fn (ParentEleve $p) => "« {$p->nom_complet} » est désormais le parent principal."
        );
    }

    public function parentDestroy(Espace $espace, ParentEleve $parent)
    {
        return $this->runAjax(
            function () use ($parent) {
                $nom = $parent->nom_complet;
                $this->parentService->supprimer($parent);
                $this->logAction('delete', 'ParentEleve', $parent->id,
                    "Suppression du parent « {$nom} »");
            },
            'Parent supprimé.'
        );
    }

    // ============================================================
    // COMPTES PORTAIL
    // ============================================================

    public function compteCreate(Espace $espace)
    {
        $parents = $this->parentRepository->parEspace($espace->id);

        return view('domain.scolarite.espaces._modal_compte', [
            'espace'  => $espace,
            'compte'  => null,
            'parents' => $parents,
            'action'  => route('scolarite.espaces.comptes.store', $espace),
            'method'  => 'POST',
            'titre'   => 'Créer un compte portail',
        ]);
    }

    public function compteStore(StoreCompteRequest $request, Espace $espace)
    {
        return $this->runAjax(
            function () use ($request, $espace) {
                $data = $request->validated();
                $data['espace_id'] = $espace->id;

                $dto = CompteDTO::fromArray($data);
                $resultat = $this->compteService->creer($dto);

                $this->logAction('create', 'Compte', $resultat['compte']->id,
                    "Création du compte portail pour « {$resultat['compte']->email} »");

                return $resultat['compte'];
            },
            fn (Compte $c) => "Compte portail créé. Les identifiants ont été envoyés à {$c->email}."
        );
    }

    public function compteEdit(Espace $espace, Compte $compte)
    {
        $parents = $this->parentRepository->parEspace($espace->id);

        return view('domain.scolarite.espaces._modal_compte', [
            'espace'  => $espace,
            'compte'  => $compte,
            'parents' => $parents,
            'action'  => route('scolarite.espaces.comptes.update', [$espace, $compte]),
            'method'  => 'PUT',
            'titre'   => 'Modifier le compte portail',
        ]);
    }

    public function compteUpdate(StoreCompteRequest $request, Espace $espace, Compte $compte)
    {
        return $this->runAjax(
            function () use ($request, $espace, $compte) {
                $data = $request->validated();
                $data['espace_id'] = $espace->id;

                $dto = CompteDTO::fromArray($data);
                $this->compteService->mettreAJour($compte, $dto);

                $this->logAction('update', 'Compte', $compte->id,
                    "Modification du compte portail « {$compte->email} »");

                return $compte;
            },
            fn (Compte $c) => "Compte portail mis à jour."
        );
    }

    public function compteRegenerer(Espace $espace, Compte $compte)
    {
        return $this->runAjax(
            function () use ($compte) {
                $this->compteService->regenererMotDePasse($compte);

                $this->logAction('update', 'Compte', $compte->id,
                    "Régénération du mot de passe du compte « {$compte->email} »");

                return $compte;
            },
            fn (Compte $c) => "Nouveau mot de passe envoyé à {$c->email}."
        );
    }

    public function compteDestroy(Espace $espace, Compte $compte)
    {
        return $this->runAjax(
            function () use ($compte) {
                $email = $compte->email;
                $this->compteService->supprimer($compte);
                $this->logAction('delete', 'Compte', $compte->id,
                    "Suppression du compte portail « {$email} »");
            },
            'Compte portail supprimé.'
        );
    }

    // ============================================================
    // REGROUPEMENT DE FRATRIE
    // ============================================================

    public function regrouperForm(Request $request)
    {
        $anneeId  = $this->anneeContext->id();
        $eleveIds = array_map('intval', $request->input('eleve_ids', []));

        // Si appelé depuis la liste des élèves (sans espace_id) :
        $eleves = empty($eleveIds)
            ? $this->espaceRepository->elevesSansEspace($anneeId)
            : \App\Domain\Scolarite\Models\Eleve::whereIn('id', $eleveIds)
                ->get(['id', 'nom', 'prenom', 'matricule', 'espace_id']);

        $espaces = Espace::actif()->orderBy('nom_famille')->get(['id', 'nom_famille']);

        return view('domain.scolarite.espaces._modal_regrouper', [
            'eleves'  => $eleves,
            'espaces' => $espaces,
            'action'  => route('scolarite.espaces.regrouper'),
        ]);
    }

    public function regrouper(RegrouperFratrieRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $data = $request->validated();

                $espaceId = $data['mode'] === 'existant' ? (int) $data['espace_id'] : null;
                $nom      = $data['mode'] === 'nouveau'  ? $data['nom_famille'] : null;

                $resultat = $this->espaceService->regrouper(
                    $data['eleve_ids'],
                    $espaceId,
                    $nom
                );

                $this->logAction(
                    'regrouper_fratrie', 'Espace', $resultat['espace_id'],
                    "Regroupement de {$resultat['eleves_affectes']} élève(s) dans l'espace « {$resultat['espace_nom']} »",
                    [
                        'eleve_ids'       => $data['eleve_ids'],
                        'espace_id'       => $resultat['espace_id'],
                        'eleves_affectes' => $resultat['eleves_affectes'],
                        'deja_affectes'   => $resultat['deja_affectes'],
                    ]
                );

                return $resultat;
            },
            function (array $r) {
                $msg = "{$r['eleves_affectes']} élève(s) affecté(s) à l'espace « {$r['espace_nom']} ».";
                if (!empty($r['deja_affectes'])) {
                    $msg .= " " . count($r['deja_affectes']) . " élève(s) déjà dans un autre espace (ignoré(s)).";
                }
                return $msg;
            }
        );
    }
}