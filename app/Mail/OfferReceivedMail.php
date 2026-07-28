<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\DomainOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OfferReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Domain $domain, public DomainOffer $offer)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Nueva oferta por %s: %s %s',
                $this->domain->name,
                $this->offer->currency,
                number_format((float) $this->offer->amount, 2)
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.offer-received',
            with: [
                'domain' => $this->domain,
                'offer' => $this->offer,
            ],
        );
    }
}
