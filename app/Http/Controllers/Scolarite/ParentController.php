<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateParentRequest;
use App\Services\ParentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class ParentController extends Controller
{
    protected ParentService $service;

    public function __construct(ParentService $service)
    {
        $this->service = $service;
    }

    /**
     * Affiche la liste des parents (server-side)
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'has_whatsapp', 'per_page']);
        $data = $this->service->listWithAggregates($filters);

        return view('admin.parents.index', [
            'parents'    => $data['data'],
            'aggregates' => $data['aggregates'],
            'filters'    => $filters,
            'pagination' => $data['pagination'],
            'page_title' => 'Parents d\'élèves',
        ]);
    }

    /**
     * Affiche la fiche détaillée d'un parent
     */
    public function show(int $parentId): View
    {
        $fiche = $this->service->getFicheParent($parentId);
        return view('admin.parents.show', [
            'fiche'      => $fiche,
            'page_title' => 'Fiche parent - ' . $fiche['parent']->nom_parent . ' ' . $fiche['parent']->prenom_parent,
        ]);
    }

    /**
     * API : Mise à jour des informations du parent (AJAX)
     */
    public function update(UpdateParentRequest $request, int $parentId): \Illuminate\Http\JsonResponse
    {
        try {
            $parent = $this->service->updateParent($parentId, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Informations mises à jour',
                'data'    => $parent
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Envoi d'un email au parent
     */
    public function sendEmail(Request $request, int $parentId): \Illuminate\Http\JsonResponse
    {
        $parent = $this->service->getParentById($parentId); // à ajouter dans le service
        if (!$parent->email) {
            return response()->json(['success' => false, 'message' => 'Aucun email renseigné'], 400);
        }

        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            Mail::raw($request->message, function ($mail) use ($parent, $request) {
                $mail->to($parent->email)
                    ->subject($request->subject);
            });
            return response()->json(['success' => true, 'message' => 'Email envoyé']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur envoi email'], 500);
        }
    }

    /**
     * Envoi d'un message WhatsApp (via API externe)
     */
    public function sendWhatsApp(Request $request, int $parentId): \Illuminate\Http\JsonResponse
    {
        $parent = $this->service->getParentById($parentId);
        $numero = $parent->whatsapp ?? $parent->telephone;
        if (!$numero) {
            return response()->json(['success' => false, 'message' => 'Aucun numéro WhatsApp/téléphone'], 400);
        }

        $request->validate(['message' => 'required|string']);

        // Exemple avec une API fictive (à adapter selon votre fournisseur)
        try {
            // Http::post('https://api.whatsapp.com/send', [...]);
            return response()->json(['success' => true, 'message' => 'WhatsApp envoyé (simulé)']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur envoi WhatsApp'], 500);
        }
    }

    /**
     * Envoi d'un SMS (via API externe)
     */
    public function sendSms(Request $request, int $parentId): \Illuminate\Http\JsonResponse
    {
        $parent = $this->service->getParentById($parentId);
        if (!$parent->telephone) {
            return response()->json(['success' => false, 'message' => 'Aucun téléphone'], 400);
        }

        $request->validate(['message' => 'required|string']);

        // Exemple API SMS (à adapter)
        try {
            // Http::post('https://api.smsprovider.com/send', [...]);
            return response()->json(['success' => true, 'message' => 'SMS envoyé (simulé)']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur envoi SMS'], 500);
        }
    }
}