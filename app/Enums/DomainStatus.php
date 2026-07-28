<?php

namespace App\Enums;

enum DomainStatus: string
{
    case Watching = 'watching';
    case ActiveProject = 'active_project';
    case ForSale = 'for_sale';
    case Sold = 'sold';
    case PendingRenewal = 'pending_renewal';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Watching => 'En cartera',
            self::ActiveProject => 'Proyecto activo',
            self::ForSale => 'En venta',
            self::Sold => 'Vendido',
            self::PendingRenewal => 'Por renovar',
            self::Expired => 'Vencido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Watching => 'secondary',
            self::ActiveProject => 'primary',
            self::ForSale => 'success',
            self::Sold => 'info',
            self::PendingRenewal => 'warning',
            self::Expired => 'danger',
        };
    }
}
