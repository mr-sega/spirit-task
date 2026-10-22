<?php

namespace App\Actions\Marketplace;

use App\Data\Marketplace\WebhookData;
use App\Models\MarketplaceEvent;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReceiveMarketplaceEvent
{
    public function __construct(
        private ApplyOrderSnapshot $applySnapshot,
        private RebuildOrderHistory $rebuildHistory,
    ) {}

    public function handle(WebhookData $data): void
    {
        DB::transaction(function () use ($data): void {
            $event = MarketplaceEvent::query()->createOrFirst(['id' => $data->id()], [
                'type' => $data->type(),
                'payload' => $data->payload,
                'created_at' => $data->createdAt(),
                'received_at' => now(),
            ]);

            if (! $event->wasRecentlyCreated) {
                if ($event->payload != $data->payload) {
                    abort(409, 'Event id was already used with a different payload.');
                }

                return;
            }

            if (! $data->isSupported()) {
                return;
            }

            $order = $this->lockOrder($data);
            $event->update(['order_id' => $order->id]);
            $this->applySnapshot->handle($data, $order);
            $this->rebuildHistory->handle($order);
        }, attempts: 3);
    }

    private function lockOrder(WebhookData $event): Order
    {
        $snapshot = $event->snapshot();

        // A unique key also serializes concurrent first deliveries for a new order.
        Order::query()->firstOrCreate(['id' => $snapshot->id()], [
            ...$snapshot->attributes(),
            'last_event_created_at' => $event->createdAt(),
            'last_event_id' => $event->id(),
        ]);

        return Order::query()->whereKey($snapshot->id())->lockForUpdate()->firstOrFail();
    }
}
