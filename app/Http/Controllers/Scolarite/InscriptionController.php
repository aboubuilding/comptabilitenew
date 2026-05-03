<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEleveInfoRequest;
use App\Services\InscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InscriptionController extends Controller
{
    protected InscriptionService $service;

    public function __construct(InscriptionService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la liste des inscriptions (server-side)
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'cycle_id', 'niveau_id', 'classe_id', 'sexe',
            'type_inscription', 'nationalite_id', 'search', 'per_page'
        ]);

        $data = $this->service->getListForView($filters);

        return view('admin.inscriptions.index', [
            'inscriptions' => $data['inscriptions'],
            'aggregates'   => $data['aggregates'],
            'filters'      => $data['filters'],
            'page_title'   => 'Inscriptions',
        ]);
    }

    /**
     * Affiche la fiche complète d'un élève (vue)
     */
    public function show(int $eleveId): View
    {
        try {
            $fiche = $this->service->getFicheForView($eleveId);
            return view('admin.inscriptions.show', [
                'fiche'      => $fiche,
                'page_title' => 'Fiche élève - ' . $fiche['eleve']->nom . ' ' . $fiche['eleve']->prenom,
            ]);
        } catch (\Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * API : Mise à jour des informations personnelles (AJAX)
     */
    public function updateEleve(UpdateEleveInfoRequest $request, int $eleveId): \Illuminate\Http\JsonResponse
    {
        try {
            $eleve = $this->service->updateEleveInfo($eleveId, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour',
                'data'    => $eleve
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Optionnel : endpoint JSON pour la liste (si nécessaire)
    public function listJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $filters = $request->only([
            'cycle_id', 'niveau_id', 'classe_id', 'sexe',
            'type_inscription', 'nationalite_id', 'search', 'per_page'
        ]);
        $result = $this->service->listWithAggregates($filters);
        return response()->json($result);
    }
}