<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Requests\StoreCaisseRequest;
use App\Http\Requests\OuvrirCaisseRequest;
use App\Http\Requests\CloturerCaisseRequest;
use App\Http\Requests\StoreCaissierRequest;
use App\Http\Requests\FilterRequest;
use App\Services\CaisseService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use LogicException;
use App\Http\Controllers\Controller;

class CaisseController extends Controller
{
    public function __construct(private CaisseService $service) {}

    // ─────────────────────────────────────────────────────────────
    // 📦 CAISSES
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /caisses
     * 📄 Vue Blade : Liste des caisses
     */
    public function index(FilterRequest $request): View
    {
        try {
            $filters = $request->validated();
            $result  = $this->service->getCaissesWithAggregates($filters);

            return view('caisses.index', [
                'caisses'    => $result['data'],
                'aggregates' => $result['aggregates'],
                'meta'       => $result['meta'],
                'filters'    => $filters,
            ]);
        } catch (LogicException $e) {
            return view('caisses.index', [
                'error' => $e->getMessage(), 'caisses' => [], 'aggregates' => [], 'meta' => [], 'filters' => []
            ]);
        }
    }

    /**
     * GET /caisses/{id}
     * 📄 Vue Blade : Détail complet d'une caisse
     */
    public function show(int $id): View
    {
        try {
            $detail = $this->service->getCaisseDetail($id);
            
            if (!$detail) {
                abort(404, 'Caisse introuvable ou accès non autorisé.');
            }

            return view('caisses.show', [
                'caisse'        => $detail['caisse'],
                'solde_actuel'  => $detail['solde_actuel'],
                'encaissements' => $detail['encaissements'],
                'decaissements' => $detail['decaissements'],
                'stats'         => $detail['stats'],
            ]);

        } catch (LogicException $e) {
            return redirect()->route('caisses.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/caisses
     * 🟢 JSON : Création d'une caisse (statut = FERME)
     */
    public function store(StoreCaisseRequest $request): JsonResponse
    {
        try {
            $caisse = $this->service->creerCaisse($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Caisse créée avec succès. Veuillez l\'ouvrir pour commencer à l\'utiliser.',
                'data'    => $this->formatCaisseBrief($caisse),
            ], 201);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/caisses/{id}/ouvrir
     * 🟢 JSON : Ouverture d'une caisse avec fond initial
     */
    public function ouvrir(int $id, OuvrirCaisseRequest $request): JsonResponse
    {
        try {
            $caisse = $this->service->ouvrirCaisse($id, (float) $request->validated('solde_initial'));

            return response()->json([
                'success' => true,
                'message' => 'Caisse ouverte avec succès.',
                'data'    => $this->formatCaisseBrief($caisse),
            ]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/caisses/{id}/cloturer
     * 🟢 JSON : Clôture avec gestion intelligente des écarts
     */
    public function cloturer(int $id, CloturerCaisseRequest $request): JsonResponse
    {
        try {
            $result = $this->service->cloturerCaisse(
                caisseId: $id,
                soldePhysique: (float) $request->validated('solde_physique'),
                motifEcarts: $request->validated('motif_ecart')
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data'    => [
                    'ecart'       => $result['ecart'],
                    'niveau'      => $result['niveau_ecart'],
                    'solde_final' => $result['solde_physique'],
                ],
            ]);
        } catch (LogicException $e) {
            $code = str_contains($e->getMessage(), 'direction') || str_contains($e->getMessage(), 'responsable') ? 403 : 422;
            return response()->json(['success' => false, 'error' => $e->getMessage()], $code);
        }
    }

    /**
     * GET /api/caisses/{id}/solde
     * 🟢 JSON : Solde théorique actuel (si ouverte)
     */
    public function solde(int $id): JsonResponse
    {
        try {
            $solde = $this->service->getSoldeActuel($id);
            return response()->json(['success' => true, 'data' => ['solde_theorique' => $solde]]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 👥 CAISSIERS
    // ─────────────────────────────────────────────────────────────

    public function caissiers(FilterRequest $request): JsonResponse
    {
        try {
            $result = $this->service->getCaissiersList($request->validated());
            return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['meta']]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    public function storeCaissier(StoreCaissierRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('caissiers/photos', 'public');
            }
            $caissier = $this->service->createCaissier($data);

            return response()->json([
                'success' => true,
                'message' => 'Caissier créé avec succès.',
                'data'    => ['id' => $caissier->id, 'nom_complet' => trim("{$caissier->nom} {$caissier->prenom}"), 'login' => $caissier->login],
            ], 201);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function destroyCaissier(int $id): JsonResponse
    {
        try {
            $this->service->deactivateCaissier($id);
            return response()->json(['success' => true, 'message' => 'Caissier désactivé.']);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 📈 REPORTING
    // ─────────────────────────────────────────────────────────────

    public function ecartReport(FilterRequest $request): JsonResponse
    {
        try {
            $report = $this->service->getEcartReport($request->validated());
            return response()->json(['success' => true, 'data' => $report]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    public function caissierMouvements(int $id, FilterRequest $request): JsonResponse
    {
        try {
            $result = $this->service->getMouvementsByCaissier($id, $request->validated());
            return response()->json(['success' => true, 'data' => $result]);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 🔧 Helper
    // ─────────────────────────────────────────────────────────────

    private function formatCaisseBrief(\App\Models\Caisse $c): array
    {
        return [
            'id'            => $c->id,
            'libelle'       => $c->libelle,
            'statut'        => (int) $c->statut,
            'statut_label'  => match((int) $c->statut) { 1 => 'Fermée', 2 => 'Ouverte', 3 => 'Clôturée', default => 'Inconnu' },
            'solde_initial' => $c->solde_initial !== null ? (float) $c->solde_initial : null,
        ];
    }
}