<?php

namespace App\Http\Controllers\domains;

use App\Enums\DomainLogType;
use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\DomainOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainOfferController extends Controller
{
    public function accept(Request $request, Domain $domain, DomainOffer $offer): RedirectResponse
    {
        return $this->resolve($request, $domain, $offer, OfferStatus::Accepted);
    }

    public function reject(Request $request, Domain $domain, DomainOffer $offer): RedirectResponse
    {
        return $this->resolve($request, $domain, $offer, OfferStatus::Rejected);
    }

    private function resolve(Request $request, Domain $domain, DomainOffer $offer, OfferStatus $status): RedirectResponse
    {
        abort_unless($offer->domain_id === $domain->id, 404);

        $offer->update(['status' => $status->value]);

        $domain->logs()->create([
            'user_id' => $request->user()->id,
            'type' => DomainLogType::OfferReceived->value,
            'description' => sprintf(
                'Oferta de %s (%s %s) marcada como "%s".',
                $offer->name ?: $offer->email,
                $offer->currency,
                number_format((float) $offer->amount, 2),
                $status->label()
            ),
        ]);

        $message = $status === OfferStatus::Accepted
            ? 'Oferta aceptada. Si vas a concretar la venta, no olvides cambiar el estado del dominio a "Vendido".'
            : 'Oferta rechazada.';

        return redirect()->route('domains.show', $domain)->with('success', $message);
    }
}
