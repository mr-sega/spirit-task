<?php

namespace App\Actions\OrderNotifications;

use App\Enums\OrderNotificationType;
use App\Models\Order;
use App\Models\OrderNotification;

class CanSendOrderNotification
{
    public function check(OrderNotification $notification, Order $order): bool
    {
        if ($notification->type->value !== $order->status) {
            return false;
        }

        if ($notification->type !== OrderNotificationType::Shipped) {
            return true;
        }

        $shipment = $order->shipment;

        if (! is_array($shipment)) {
            return false;
        }

        foreach (['carrier', 'tracking_number', 'tracking_url'] as $field) {
            if (! isset($shipment[$field]) || $shipment[$field] === '') {
                return false;
            }
        }

        return true;
    }
}
