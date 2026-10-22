<?php

namespace App\Actions\OrderNotifications;

use App\Enums\OrderNotificationType;
use App\Mail\Orders\OrderCancelledMail;
use App\Mail\Orders\OrderPaidMail;
use App\Mail\Orders\OrderShippedMail;
use App\Models\Order;
use Illuminate\Mail\Mailable;

class CreateOrderMail
{
    public function handle(OrderNotificationType $type, Order $order): Mailable
    {
        if ($type === OrderNotificationType::Paid) {
            $order->load('items');

            return new OrderPaidMail($order);
        }

        if ($type === OrderNotificationType::Shipped) {
            return new OrderShippedMail($order);
        }

        $wasPaid = $order->paid_at !== null || $order->history()->where('status', 'paid')->exists();

        return new OrderCancelledMail($order, $wasPaid);
    }
}
