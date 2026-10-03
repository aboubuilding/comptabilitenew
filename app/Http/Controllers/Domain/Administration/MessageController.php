<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Domain\Administration\DTO\MessageDTO;
use App\Domain\Administration\Exports\MessagesExport;
use App\Domain\Administration\Models\ContactTiers;
use App\Domain\Administration\Models\Message;
use App\Domain\Administration\Models\ModeleMessage;
use App\Domain\Administration\Repositories\MessageRepository;
use App\Domain\Administration\Services\MessageService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Administration\StoreMessageRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Module 6.3 — Communications.
 */
class MessageController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function __construct(
        private readonly MessageService $service,
        private readonly MessageRepository $repository
    ) {
        $this->middleware('permission:communications.voir')->only(['index', 'show', 'export', 'historique']);
        $this->middleware('permission:communications.envoyer')->only(['create', 'store', 'edit', 'update', 'envoyer', 'destroy']);
    }

    public function index(Request $request)
    {
        $filtres = $request->only(['statut', 'type', 'recherche', 'date_debut', 'date_fin']);
        $messages = $this->repository->listePaginee($filtres, 15);

        $stats = [
            'total'      => Message::actif()->count(),
            'brouillons' => Message::actif()->where('statut', 'brouillon')->count(),
            'envoyes'    => Message::actif()->where('statut', 'envoye')->count(),
            'en_cours'   => Message::actif()->where('statut', 'en_cours')->count(),
        ];

        return view('domain.administration.messages.index', compact('messages', 'stats', 'filtres'));
    }

    public function create()
    {
        $modeles = ModeleMessage::actif()->orderBy('libelle')->get();
        $tiers   = ContactTiers::actif()->orderBy('nom')->get();

        return view('domain.administration.messages._modal_form', [
            'message' => null,
            'modeles' => $modeles,
            'tiers'   => $tiers,
            'action'  => route('administration.messages.store'),
            'method'  => 'POST',
            'titre'   => 'Nouveau message',
        ]);
    }

    public function store(StoreMessageRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto     = MessageDTO::fromArray($request->validated());
                $ciblage = $this->extraireCiblage($request);
                $envoyer = (bool) $request->input('envoyer', 0);

                $message = $this->service->creer($dto, $ciblage, $envoyer);

                $this->logAction(
                    'create', 'Message', $message->id,
                    "Création du message « {$message->sujet} » ({$message->nb_destinataires} destinataire(s))",
                    ['statut' => $message->statut?->value]
                );
            },
            'Message enregistré avec succès.'
        );
    }

    public function show(Message $message)
    {
        $message->load(['destinataires', 'modele', 'expediteur']);
        return view('domain.administration.messages.show', compact('message'));
    }

    public function edit(Message $message)
    {
        $message->load('destinataires');
        $modeles = ModeleMessage::actif()->orderBy('libelle')->get();
        $tiers   = ContactTiers::actif()->orderBy('nom')->get();

        return view('domain.administration.messages._modal_form', [
            'message' => $message,
            'modeles' => $modeles,
            'tiers'   => $tiers,
            'action'  => route('administration.messages.update', $message),
            'method'  => 'PUT',
            'titre'   => 'Modifier le message',
        ]);
    }

    public function update(StoreMessageRequest $request, Message $message)
    {
        return $this->runAjax(
            function () use ($request, $message) {
                $dto = MessageDTO::fromArray($request->validated());
                $this->service->mettreAJour($message, $dto);

                $this->logAction('update', 'Message', $message->id, "Modification du message « {$message->sujet} »");
            },
            'Message mis à jour avec succès.'
        );
    }

    /**
     * Déclenche l'envoi d'un brouillon.
     */
    public function envoyer(Message $message)
    {
        return $this->runAjax(
            function () use ($message) {
                $this->service->envoyer($message);
                $this->logAction('send', 'Message', $message->id, "Envoi du message « {$message->sujet} »");
            },
            'Message en cours d\'envoi.'
        );
    }

    public function destroy(Message $message)
    {
        return $this->runAjax(
            function () use ($message) {
                $sujet = $message->sujet;
                $this->service->supprimer($message);
                $this->logAction('delete', 'Message', $message->id, "Suppression du message « {$sujet} »");
            },
            'Message supprimé avec succès.'
        );
    }

    public function export(Request $request)
    {
        $filtres  = $request->only(['statut', 'type']);
        $messages = $this->repository->listePourExport($filtres);

        $this->logAction('export', 'Message', null, 'Export Excel des communications', $filtres);

        return Excel::download(
            new MessagesExport($messages),
            'communications_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Historique des communications d'un destinataire (par email).
     */
    public function historique(Request $request)
    {
        $email = $request->query('email');
        $lignes = $email ? $this->service->historiqueParEmail($email) : collect();

        return view('domain.administration.messages.historique', compact('email', 'lignes'));
    }

    protected function extraireCiblage(Request $request): array
    {
        $type = $request->input('ciblage_type');

        return match ($type) {
            'parents' => ['type' => 'parents', 'parent_ids' => $request->input('parent_ids', [])],
            'classe'  => ['type' => 'classe',  'classe_id'  => $request->input('classe_id')],
            'niveau'  => ['type' => 'niveau',  'niveau_id'  => $request->input('niveau_id')],
            'tiers'   => ['type' => 'tiers',   'tiers_ids'  => $request->input('tiers_ids', [])],
            'libre'   => ['type' => 'libre',   'emails_libres' => $request->input('emails_libres', [])],
            default   => ['type' => 'libre',   'emails_libres' => []],
        };
    }
}