<?php

namespace App\Enums;

enum DomainLogType: string
{
    case Note = 'note';
    case StatusChange = 'status_change';
    case Renewal = 'renewal';
    case PriceChange = 'price_change';
    case OfferReceived = 'offer_received';
    case Sale = 'sale';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Nota',
            self::StatusChange => 'Cambio de estado',
            self::Renewal => 'Renovación',
            self::PriceChange => 'Cambio de costo',
            self::OfferReceived => 'Oferta recibida',
            self::Sale => 'Venta',
            self::System => 'Sistema',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Note => 'secondary',
            self::StatusChange => 'primary',
            self::Renewal => 'info',
            self::PriceChange => 'warning',
            self::OfferReceived => 'success',
            self::Sale => 'success',
            self::System => 'secondary',
        };
    }
}
