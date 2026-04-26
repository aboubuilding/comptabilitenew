<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiFilterRequest;
use App\Http\Requests\StoreDepenseRequest;
use App\Http\Requests\RejeterDepenseRequest;
use App\Services\DepenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DepenseController extends Controller
{
    public function __construct(private DepenseService $service) {}

    public function index(ApiFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        return response()->json([
            'success' => true,
            'data'    => $this->service->getDepensesWithStats($filters),
        ]);
    }

    public function store(StoreDepenseRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('justificatif')) {
            $data['justificatif'] = $request->file('justificatif')->store('depenses/justificatifs', 'public');
        }

        $depense = $this->service->enregistrerDepense($data, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => $depense->montant_prevu < DepenseService::SEUIL_VALIDATION_AUTO
                ? 'Dépense enregistrée et automatiquement validée.'
                : 'Dépense enregistrée, en attente de validation.',
            'data'    => $depense->load('demandeur:id,name'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->service->getDepenseWithDetails($id),
        ]);
    }

    public function valider(int $id, Request $request): JsonResponse
    {
        $depense = \App\Models\Depense::findOrFail($id);
        $user    = $request->user();

        if ($depense->montant_prevu >= DepenseService::SEUIL_VALIDATION_AUTO) {
            // Remplace par ta logique de rôle (spatie, custom, etc.)
            $isAuthorized = $user->hasRole(['admin', 'directeur']) || $user->isAdmin();
            throw_unless($isAuthorized, AccessDeniedHttpException::class, 'Validation réservée aux administrateurs ou directeurs pour les montants ≥ 150 000.');
        }

        $this->service->validerDepense($id, $user->id);

        return response()->json(['success' => true, 'message' => 'Dépense validée avec succès.']);
    }

    public function rejeter(int $id, RejeterDepenseRequest $request): JsonResponse
    {
        $this->service->rejeterDepense($id, $request->validated()['motif'], $request->user()->id);
        return response()->json(['success' => true, 'message' => 'Dépense rejetée.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = app(\App\Repositories\Contracts\DepenseRepositoryInterface::class)->delete($id);
        return response()->json(['success' => $deleted, 'message' => $deleted ? 'Dépense supprimée.' : 'Échec de la suppression.']);
    }
}