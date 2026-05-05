<?php
namespace App\Http\Controllers\Admin_old;

use App\Http\Controllers\Controller;
use App\Services\CaisseJournalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CaisseJournalController extends Controller
{
    protected CaisseJournalService $service;

    public function __construct(CaisseJournalService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('admin.caisses.journal', [
            'page_title' => 'Journal de caisse'
        ]);
    }

    /**
     * Liste des caisses (pour sélecteur)
     */
    public function caissesList(Request $request): JsonResponse
    {
        $result = $this->service->listeCaisses($request->only(['statut', 'annee_id', 'per_page']));
        return response()->json(['success' => true, 'data' => $result['data'], 'meta' => $result['pagination']]);
    }

    /**
     * Récupère le journal (encaissements + dépenses) selon les filtres
     */
    public function journal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caisse_id'   => 'nullable|integer|exists:caisses,id',
            'date_debut'  => 'nullable|date',
            'date_fin'    => 'nullable|date|after_or_equal:date_debut',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->service->journalCaisse(
            $validated['caisse_id'] ?? null,
            $validated['date_debut'] ?? null,
            $validated['date_fin'] ?? null,
            $request->only(['per_page'])
        );

        return response()->json(['success' => true, 'data' => $result]);
    }
}
