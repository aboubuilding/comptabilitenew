<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\DTO\DestinataireDTO;
use App\Domain\Administration\DTO\MessageDTO;
use App\Domain\Administration\Models\Message;
use App\Domain\Administration\Models\MessageDestinataire;
use App\Domain\Administration\Repositories\MessageRepository;
use App\Domain\Administration\Types\StatutMessage;
use App\Jobs\SendMessageJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logique métier des communications.
 */
class MessageService
{
    public function __construct(
        private readonly MessageRepository $repository,
        private readonly DestinataireResolverService $resolver
    ) {}

    /**
     * Crée un message en brouillon (ou prêt à envoyer).
     *
     * @param  array  $ciblage  Description du ciblage (type, ids, emails libres...)
     * @param  bool   $envoyerImmediatement
     */
    public function creer(MessageDTO $dto, array $ciblage, bool $envoyerImmediatement = false): Message
    {
        return DB::transaction(function () use ($dto, $ciblage, $envoyerImmediatement) {

            // 1) Créer le message
            $message = $this->repository->create(array_merge($dto->toArray(), [
                'expediteur_id'  => Auth::id(),
                'expediteur_nom' => Auth::user()?->nom_complet,
                'statut'         => StatutMessage::BROUILLON->value,
            ]));

            // 2) Résoudre les destinataires
            $destinataires = $this->resolver->resoudre($ciblage);

            if (empty($destinataires)) {
                throw new RuntimeException('Aucun destinataire valide trouvé.');
            }

            // 3) Insérer les destinataires
            $lignes = array_map(fn (DestinataireDTO $d) => $d->toArray($message->id), $destinataires);
            MessageDestinataire::insert($lignes);

            // 4) Mettre à jour le compteur
            $message->update(['nb_destinataires' => count($lignes)]);

            // 5) Envoi immédiat si demandé
            if ($envoyerImmediatement) {
                $this->envoyer($message);
            }

            return $message->fresh(['destinataires', 'modele', 'expediteur']);
        });
    }

    /**
     * Met à jour un brouillon uniquement.
     */
    public function mettreAJour(Message $message, MessageDTO $dto): Message
    {
        if ($message->statut !== StatutMessage::BROUILLON) {
            throw new RuntimeException('Seul un brouillon peut être modifié.');
        }

        return DB::transaction(function () use ($message, $dto) {
            $this->repository->update($message, $dto->toArray());
            return $message->fresh(['destinataires', 'modele']);
        });
    }

    /**
     * Marque le message comme « en cours » et dispatche le job d'envoi.
     */
    public function envoyer(Message $message): void
    {
        if ($message->statut === StatutMessage::ENVOYE) {
            throw new RuntimeException('Ce message a déjà été envoyé.');
        }

        $message->update([
            'statut'     => StatutMessage::EN_COURS->value,
            'date_envoi' => now(),
        ]);

        SendMessageJob::dispatch($message->id);
    }

    /**
     * Supprime (logique) un message.
     */
    public function supprimer(Message $message): void
    {
        $this->repository->delete($message);
    }

    /**
     * Recalcule les compteurs et le statut global à partir des destinataires.
     * Appelé par le job après envoi.
     */
    public function recalculerStatut(Message $message): void
    {
        $total  = $message->destinataires()->count();
        $ok     = $message->destinataires()->where('statut_envoi', 'envoye')->count();
        $ko     = $message->destinataires()->where('statut_envoi', 'echec')->count();

        $statut = match(true) {
            $ok === 0 && $ko === 0 => StatutMessage::EN_COURS,
            $ok === $total         => StatutMessage::ENVOYE,
            $ko === $total         => StatutMessage::ECHEC,
            default                => StatutMessage::PARTIEL,
        };

        $message->update([
            'statut'      => $statut->value,
            'nb_envoyes'  => $ok,
            'nb_echecs'   => $ko,
        ]);
    }

    /**
     * Historique des communications envoyées à un destinataire (email).
     */
    public function historiqueParEmail(string $email)
    {
        return MessageDestinataire::where('email', $email)
            ->with('message')
            ->latest()
            ->get();
    }
}