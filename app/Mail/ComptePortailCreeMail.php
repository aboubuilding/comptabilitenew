<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email envoyé au parent lors de la création de son compte portail.
 */
class ComptePortailCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $parentNom,
        public readonly string $email,
        public readonly string $motDePasse,
        public readonly string $nomFamille = '—',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte portail parent — ' . config('app.name', 'École Internationale Mariam'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compte_portail_cree',
            with: [
                'parentNom'   => $this->parentNom,
                'email'       => $this->email,
                'motDePasse'  => $this->motDePasse,
                'nomFamille'  => $this->nomFamille,
                'etablissement' => config('app.name', 'École Internationale Mariam'),
            ],
        );
    }
}