<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Requests\StoreDepenseRequest;
use App\Http\Requests\RejeterDepenseRequest;
use App\Services\DepenseService;
use App\Types\StatutDepense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DepenseController extends Controller
{
    public function __construct(private DepenseService $service) {}

    /**
     * GET /depenses
     * 📄 Retourne une VUE Blade avec la liste et les agrégats
     */
    public function index(Request $request): View
    {
        try {
            $filters = array_filter($request->only(['statut', 'date_debut', 'date_fin', 'caisse_id']));
            $result  = $this->service->getDepensesWithAggregates($filters);

            return view('depenses.index', [
                'depenses'   => $result['data'],
                'aggregates' => $result['aggregates'],
                'meta'       => $result['meta'],
                'filters'    => $filters,
            ]);
        } catch (LogicException $e) {
            // Retourne la vue avec un message d'erreur au lieu de planter
            return view('depenses.index', ['error' => $e->getMessage(), 'depenses' => [], 'aggregates' => [], 'meta' => [], 'filters' => []]);
        }
    }

    /**
     * POST /api/depenses
     * 🟢 Création via JSON
     */
    public function store(StoreDepenseRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('justificatif_demande')) {
                $data['justificatif_demande'] = $request->file('justificatif_demande')
                    ->store('depenses/justificatifs', 'public');
            }

            $depense = $this->service->enregistrerDepense($data);
            $message = $depense->montant < DepenseService::SEUIL_VALIDATION_AUTO
                ? 'Dépense enregistrée et automatiquement validée.'
                : 'Dépense enregistrée, en attente de validation.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $this->formatDepenseBrief($depense),
            ], 201);

        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/depenses/{id}
     * 🔍 Détail via JSON
     */
    public function show(int $id): JsonResponse
    {
        try {
            $depense = $this->service->getDepenseById($id);
            
            if (!$depense) {
                throw new NotFoundHttpException('Dépense introuvable.');
            }

            return response()->json([
                'success' => true,
                'data'    => $this->formatDepenseDetail($depense, $this->service->isAuthorizedViewer()),
            ]);

        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    /**
     * PUT /api/depenses/{id}/valider
     * ✅ Validation manuelle via JSON
     */
    public function valider(int $id, Request $request): JsonResponse
    {
        try {
            // La vérification des rôles et du seuil est déléguée au service
            $this->service->validerDepense($id);

            return response()->json(['success' => true, 'message' => 'Dépense validée avec succès.']);

        } catch (LogicException $e) {
            $status = str_contains($e->getMessage(), 'administrateurs') ? 403 : 422;
            return response()->json(['success' => false, 'error' => $e->getMessage()], $status);
        }
    }

    /**
     * PUT /api/depenses/{id}/rejeter
     * ❌ Rejet avec motif via JSON
     */
    public function rejeter(int $id, RejeterDepenseRequest $request): JsonResponse
    {
        try {
            $this->service->rejeterDepense(
                id: $id,
                motif: $request->validated('motif_rejet')
            );

            return response()->json(['success' => true, 'message' => 'Dépense rejetée.']);

        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * DELETE /api/depenses/{id}
     * 🗑️ Suppression logique via JSON
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $repo    = app(\App\Repositories\Contracts\DepenseRepositoryInterface::class);
            $depense = $repo->find($id);

            if (!$depense) {
                throw new NotFoundHttpException('Dépense introuvable.');
            }

            $user    = $this->service->getCurrentUser();
            $isOwner = $depense->utilisateur_id === $user?->id;
            $isAdmin = $this->service->isAuthorizedViewer();

            if (!$isOwner && !$isAdmin) {
                throw new AccessDeniedHttpException('Suppression non autorisée.');
            }
            if ($depense->statut_depense !== StatutDepense::EN_ATTENTE && !$isAdmin) {
                throw new AccessDeniedHttpException('Suppression impossible : dépense déjà traitée.');
            }

            $deleted = $repo->delete($id);

            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'Dépense supprimée.' : 'Échec de la suppression.',
            ]);

        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Erreur serveur inattendue.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 🔧 Helpers de formatage JSON
    // ─────────────────────────────────────────────────────────────

    private function formatDepenseBrief(\App\Models\Depense $d): array
    {
        return [
            'id'             => $d->id,
            'libelle'        => $d->libelle,
            'montant'        => (float) $d->montant,
            'statut_depense' => $d->statut_depense,
            'statut_label'   => StatutDepense::label($d->statut_depense),
            'caisse_libelle' => $d->caisse?->libelle,
        ];
    }

    private function formatDepenseDetail(\App\Models\Depense $d, bool $isViewer): array
    {
        return [
            'id'                     => $d->id,
            'libelle'                => $d->libelle,
            'beneficiaire'           => $d->beneficiaire,
            'motif_depense'          => $d->motif_depense,
            'montant'                => (float) $d->montant,
            'date_depense'           => $d->date_depense?->format('Y-m-d'),
            'statut_depense'         => $d->statut_depense,
            'statut_label'           => StatutDepense::label($d->statut_depense),
            'justificatif_demande'   => $d->justificatif_demande ? asset('storage/' . $d->justificatif_demande) : null,
            'caisse_id'              => $d->caisse_id,
            'caisse_libelle'         => $d->caisse?->libelle,
            'createur_nom'           => $d->demandeur?->name,
            // 🔐 Masquage conditionnel pour les non-admins
            'validateur_nom'         => $isViewer && $d->validateur ? $d->validateur->name : null,
            'date_validation'        => $isViewer ? $d->date_validation?->format('Y-m-d') : null,
            'motif_rejet'            => $isViewer && $d->statut_depense === StatutDepense::REFUSEE ? $d->motif_rejet : null,
        ];
    }
}