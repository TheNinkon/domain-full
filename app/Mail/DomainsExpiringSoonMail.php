<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DomainsExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection<int, \App\Models\Domain> $domains
     */
    public function __construct(public Collection $domains)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%d dominio(s) por vencer en los próximos 7 días', $this->domains->count()),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.domains-expiring-soon',
            with: ['domains' => $this->domains],
        );
    }
}
