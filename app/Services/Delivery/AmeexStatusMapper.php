<?php

namespace App\Services\Delivery;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;

class AmeexStatusMapper
{
    public static function mapToShipmentStatus(
        ?string $statut,
        ?string $statutName = null,
        ?string $subStatut = null,
        ?string $subStatutName = null,
    ): ?ShipmentStatus {
        $haystack = strtolower(implode(' ', array_filter([
            $statut,
            $statutName,
            $subStatut,
            $subStatutName,
        ])));

        if ($haystack === '') {
            return null;
        }

        if (self::containsAny($haystack, ['livr', 'delivered', 'livre'])) {
            return ShipmentStatus::Delivered;
        }

        if (self::containsAny($haystack, ['retour', 'returned', 'refus'])) {
            return ShipmentStatus::Returned;
        }

        if (self::containsAny($haystack, ['echec', 'failed', 'annul', 'cancel'])) {
            return ShipmentStatus::Failed;
        }

        if (self::containsAny($haystack, ['transit', 'achemin', 'hub', 'centre'])) {
            return ShipmentStatus::InTransit;
        }

        if (self::containsAny($haystack, ['ramass', 'pickup', 'collect', 'enleve'])) {
            return ShipmentStatus::PickedUp;
        }

        if (self::containsAny($haystack, ['attent', 'pending', 'nouveau', 'new', 'cree'])) {
            return ShipmentStatus::Pending;
        }

        return null;
    }

    public static function mapToOrderStatus(?ShipmentStatus $shipmentStatus): ?OrderStatus
    {
        return match ($shipmentStatus) {
            ShipmentStatus::Delivered => OrderStatus::Delivered,
            ShipmentStatus::Returned => OrderStatus::Returned,
            ShipmentStatus::InTransit, ShipmentStatus::PickedUp => OrderStatus::Shipped,
            default => null,
        };
    }

    /** @param  array<string, mixed>  $statusList */
    public static function labelFromStatusList(array $statusList, ?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        return $statusList[$code] ?? $statusList[(string) $code] ?? null;
    }

    protected static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
