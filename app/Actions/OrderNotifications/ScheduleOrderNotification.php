<?php

namespace App\Actions\OrderNotifications;

use App\Enums\OrderNotificationState;
use App\Enums\OrderNotificationType;
use App\Jobs\SendOrderNotification;
use App\Models\Order;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\Queue;

class ScheduleOrderNotification
{
    // Called inside the webhook transaction while the order row is locked.
    public function handle(Order $order): void
    {
        $type = OrderNotificationType::tryFrom($order->status);

        if ($type === null) {
            return;
        }

        $notification = OrderNotification::query()->firstOrCreate([
            'order_id' => $order->id,
            'type' => $type,
        ], ['state' => OrderNotificationState::Pending]);

        if (! $notification->wasRecentlyCreated && $notification->state !== OrderNotificationState::Suppressed) {
            return;
        }

        if ($notification->state === OrderNotificationState::Suppressed) {
            $notification->update(['state' => OrderNotificationState::Pending, 'attempt_id' => null]);
        }

        // The database queue uses the same connection and commits with the outbox.
        Queue::connection('database')->push(new SendOrderNotification($notification->id));
    }
}
