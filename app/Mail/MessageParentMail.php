<?php

namespace App\Mail;

use App\Domain\Administration\Models\Message;
use App\Domain\Administration\Models\MessageDestinataire;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable générique pour les communications du module.
 * Le corps est déjà rendu (variables remplacées côté service ou à la volée).
 */
class MessageParentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly MessageDestinataire $destinataire
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->sujet,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.message_parent',
            with: [
                'sujet'         => $this->message->sujet,
                'corps'         => $this->rendreVariables($this->message->corps),
                'destinataire'  => $this->destinataire,
            ],
        );
    }

    /**
     * Remplace les variables {{parent}}, {{eleve}}... dans le corps.
     */
    protected function rendreVariables(string $corps): string
    {
        $remplacements = [
            '{{parent}}'        => $this->destinataire->nom ?? '',
            '{{etablissement}}' => config('app.name', 'École Internationale Mariam'),
        ];

        return str_replace(array_keys($remplacements), array_values($remplacements), $corps);
    }
}