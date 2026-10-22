<?php

namespace App\Actions\OrderNotifications;

use App\Enums\OrderNotificationState;
use App\Models\Order;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DeliverOrderNotification
{
    public function __construct(
        private CanSendOrderNotification $eligibility,
        private CreateOrderMail $createMail,
    ) {}

    public function handle(int $id, string $attempt): void
    {
        // Never retry a transaction that has already contacted SMTP.
        DB::transaction(function () use ($id, $attempt): void {
            $record = OrderNotification::query()->findOrFail($id);
            $order = Order::query()->whereKey($record->order_id)->lockForUpdate()->firstOrFail();
            $notification = OrderNotification::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if ($notification->state !== OrderNotificationState::Sending || $notification->attempt_id !== $attempt) {
                return;
            }

            if (! $this->eligibility->check($notification, $order)) {
                $notification->update(['state' => OrderNotificationState::Suppressed]);

                return;
            }

            Mail::to($order->customer_email)->send($this->createMail->handle($notification->type, $order));
            $notification->update(['state' => OrderNotificationState::Sent, 'sent_at' => now()]);
        }, attempts: 1);
    }
}
