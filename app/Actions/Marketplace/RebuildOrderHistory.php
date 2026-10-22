<?php

namespace App\Actions\Marketplace;

use App\Data\Marketplace\WebhookData;
use App\Models\MarketplaceEvent;
use App\Models\Order;

class RebuildOrderHistory
{
    public function __construct(
        private CompareEventVersions $versions,
        private BuildStatusTimeline $timeline,
    ) {}

    public function handle(Order $order): void
    {
        $events = MarketplaceEvent::query()->where('order_id', $order->id)->get()
            ->map(fn (MarketplaceEvent $event): WebhookData => new WebhookData($event->payload))
            ->all();

        usort($events, $this->versions->between(...));
        $history = $this->timeline->build($events);
        $order->history()->delete();

        foreach ($history as $position => $transition) {
            $order->history()->create([...$transition, 'position' => $position]);
        }
    }
}
