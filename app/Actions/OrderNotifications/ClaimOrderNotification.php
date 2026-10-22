<?php

namespace App\Actions\OrderNotifications;

use App\Enums\OrderNotificationState;
use App\Models\Order;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimOrderNotification
{
    public function __construct(private CanSendOrderNotification $eligibility) {}

    public function handle(int $id): ?string
    {
        return DB::transaction(function () use ($id): ?string {
            $record = OrderNotification::query()->find($id);

            if ($record === null) {
                return null;
            }

            // Always lock order first, matching the webhook's lock order.
            $order = Order::query()->whereKey($record->order_id)->lockForUpdate()->firstOrFail();
            $notification = OrderNotification::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if ($notification->state !== OrderNotificationState::Pending) {
                return null;
            }

            if (! $this->eligibility->check($notification, $order)) {
                $notification->update(['state' => OrderNotificationState::Suppressed]);

                return null;
            }

            $attempt = (string) Str::uuid();
            $notification->update([
                'state' => OrderNotificationState::Sending,
                'attempt_id' => $attempt,
                'claimed_at' => now(),
            ]);

            return $attempt;
        }, attempts: 3);
    }
}
