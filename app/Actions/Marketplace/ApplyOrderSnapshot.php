<?php

namespace App\Actions\Marketplace;

use App\Data\Marketplace\WebhookData;
use App\Models\Order;

class ApplyOrderSnapshot
{
    public function __construct(private CompareEventVersions $versions) {}

    public function handle(WebhookData $event, Order $order): void
    {
        if ($this->versions->againstOrder($event, $order) < 0) {
            return;
        }

        $snapshot = $event->snapshot();
        $order->update([
            ...$snapshot->attributes(),
            'last_event_created_at' => $event->createdAt(),
            'last_event_id' => $event->id(),
        ]);

        $order->items()->delete();

        foreach ($snapshot->items() as $position => $item) {
            $order->items()->create([
                'position' => $position,
                'sku' => $item['sku'],
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
        }
    }
}
