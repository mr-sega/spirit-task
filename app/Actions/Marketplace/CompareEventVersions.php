<?php

namespace App\Actions\Marketplace;

use App\Data\Marketplace\WebhookData;
use App\Models\Order;

class CompareEventVersions
{
    public function againstOrder(WebhookData $event, Order $order): int
    {
        $snapshotTime = $event->snapshot()->updatedAt() <=> $order->updated_at;

        if ($snapshotTime !== 0) {
            return $snapshotTime;
        }

        $eventTime = $event->createdAt() <=> $order->last_event_created_at;

        if ($eventTime !== 0) {
            return $eventTime;
        }

        return strcmp($event->id(), $order->last_event_id);
    }

    public function between(WebhookData $left, WebhookData $right): int
    {
        $snapshotTime = $left->snapshot()->updatedAt() <=> $right->snapshot()->updatedAt();

        if ($snapshotTime !== 0) {
            return $snapshotTime;
        }

        $eventTime = $left->createdAt() <=> $right->createdAt();

        if ($eventTime !== 0) {
            return $eventTime;
        }

        return strcmp($left->id(), $right->id());
    }
}
