<?php

namespace App\Http\Controllers\Admin\Parametre;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClasseRequest;
use App\Http\Requests\UpdateClasseRequest;
use App\Services\ClasseService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ClasseController extends Controller
{
    protected ClasseService $service;

    public function __construct(ClasseService $service)
    {
        $this->service = $service;
    }

    /**
     * Récupère l'ID de l'année en cours depuis la session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Affiche la vue avec toutes les classes (chargées depuis le service)
     * On peut filtrer par année courante si souhaité
     */
    public function index(): View
    {
        $anneeId = $this->getCurrentAnneeId();
        $classes = $this->service->getAllFormatted($anneeId); // à adapter dans service

        return view('admin.classes.index', [
            'classes'    => $classes,
            'page_title' => 'Classes',
        ]);
    }

    /**
     * API : Liste des classes (JSON) - filtrée par année courante
     */
    public function list(): JsonResponse
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
        }

        $classes = $this->service->getByAnnee($anneeId);
        return response()->json(['success' => true, 'data' => $classes]);
    }

    /**
     * API : Détail d'une classe
     */
    public function show(int $id): JsonResponse
    {
        try {
            $classe = $this->service->show($id);
            return response()->json(['success' => true, 'data' => $classe]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Classe non trouvée'], 404);
        }
    }

    /**
     * API : Création - l'année courante est ajoutée automatiquement
     */
    public function store(StoreClasseRequest $request): JsonResponse
    {
        try {
            $anneeId = $this->getCurrentAnneeId();
            if (!$anneeId) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
            }

            $data = $request->validated();
            $data['annee_id'] = $anneeId;

            $classe = $this->service->store($data);
            return response()->json([
                'success' => true,
                'message' => 'Classe créée avec succès',
                'data'    => $classe->only(['id', 'libelle'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Mise à jour - l'année courante est ajoutée automatiquement
     */
    public function update(UpdateClasseRequest $request, int $id): JsonResponse
    {
        try {
            $anneeId = $this->getCurrentAnneeId();
            if (!$anneeId) {
                return response()->json(['success' => false, 'message' => 'Aucune année scolaire en session.'], 400);
            }

            $data = $request->validated();
            $data['annee_id'] = $anneeId;

            $this->service->update($id, $data);
            return response()->json(['success' => true, 'message' => 'Classe modifiée']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API : Suppression
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if ($this->service->hasRelatedData($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette classe est utilisée ailleurs (élèves, emplois du temps...). Suppression impossible.'
                ], 409);
            }

            $this->service->destroy($id);
            return response()->json(['success' => true, 'message' => 'Classe supprimée']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}