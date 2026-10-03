<?php

namespace App\Jobs;

use App\Domain\Administration\Models\Message;
use App\Domain\Administration\Models\MessageDestinataire;
use App\Domain\Administration\Services\MessageService;
use App\Mail\MessageParentMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoi groupé d'un message via la queue.
 * Traite chaque destinataire un par un pour tracer succès/échec.
 */
class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 600;

    public function __construct(
        public readonly int $messageId
    ) {}

    public function handle(MessageService $service): void
    {
        $message = Message::with('destinataires')->find($this->messageId);
        if (!$message) {
            return;
        }

        foreach ($message->destinataires as $destinataire) {
            // Éviter de renvoyer si déjà envoyé (reprise sur erreur)
            if ($destinataire->statut_envoi?->value === 'envoye') {
                continue;
            }

            try {
                Mail::to($destinataire->email)
                    ->send(new MessageParentMail($message, $destinataire));

                $destinataire->update([
                    'statut_envoi' => 'envoye',
                    'date_envoi'   => now(),
                    'erreur'       => null,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Échec envoi message #{$message->id} → {$destinataire->email} : {$e->getMessage()}");

                $destinataire->update([
                    'statut_envoi' => 'echec',
                    'erreur'       => substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        // Recalcule le statut global du message
        $service->recalculerStatut($message);
    }
}