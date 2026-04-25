<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;


    public $mailData;
    public $objet;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($objet, $mailData)
    {
        $this->mailData = $mailData;
        $this->objet = $objet;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->objet
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'admin.cheque.facture',
            with: [
                'title'               => $this->mailData['title'] ?? $this->objet,
                'body'                => $this->mailData['body'] ?? '',
                'paiement'            => $this->mailData['paiement'] ?? null,
                'inscription'         => $this->mailData['inscription'] ?? null,
                'annee'               => $this->mailData['annee'] ?? null,
                'details'             => $this->mailData['details'] ?? [],
                'montant_deja_payer'  => $this->mailData['montant_deja_payer'] ?? 0,
                'montant_scolarite'   => $this->mailData['montant_scolarite'] ?? 0,
                'reste'               => $this->mailData['reste'] ?? 0,
                'cheque'              => $this->mailData['cheque'] ?? null,
                'name'                => $this->mailData['name'] ?? '',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
