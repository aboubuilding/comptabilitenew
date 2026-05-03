<?php

namespace App\Http\Controllers\Caisse;

use App\Services\DecaissementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Controllers\Controller;

class DecaissementController extends Controller
{
    public function __construct(private DecaissementService $service) {}

    /**
     * GET /decaissements
     * 📄 Retourne une VUE Blade avec la liste et les agrégats
     */
    public function index(Request $request): View
    {
        try {
            $filters = array_filter($request->only(['date_debut', 'date_fin', 'caisse_id']));
            $result  = $this->service->getDecaissementsWithAggregates($filters);

            return view('decaissements.index', [
                'decaissements' => $result['data'],
                'aggregates'    => $result['aggregates'],
                'meta'          => $result['meta'],
                'filters'       => $filters,
            ]);

        } catch (LogicException $e) {
            // Fallback gracieux : affiche la vue avec un message d'erreur
            return view('decaissements.index', [
                'error'         => $e->getMessage(),
                'decaissements' => [],
                'aggregates'    => [],
                'meta'          => [],
                'filters'       => [],
            ]);
        }
    }

    /**
     * GET /decaissements/{id}
     * 🔍 Détail d'un décaissement via JSON
     */
    public function show(int $id): JsonResponse
    {
        try {
            $mvt = $this->service->getDecaissementById($id);
            
            if (!$mvt) {
                throw new NotFoundHttpException('Décaissement introuvable ou accès non autorisé.');
            }

            return response()->json([
                'success' => true,
                'data'    => $this->service->formatDecaissement($mvt, $this->service->isAuthorizedViewer()),
            ]);

        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }

    /**
     * GET /decaissements/stats
     * 📊 Statistiques agrégées via JSON (pour dashboard / graphiques)
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $filters = array_filter($request->only(['date_debut', 'date_fin', 'caisse_id']));
            $result  = $this->service->getDecaissementsWithAggregates($filters);

            return response()->json([
                'success' => true,
                'data'    => $result['aggregates'],
                'meta'    => ['total_global' => $result['meta']['montant_total']],
            ]);

        } catch (LogicException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
        }
    }
}