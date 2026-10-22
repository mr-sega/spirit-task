<?php

namespace App\Actions\Marketplace;

use App\Data\Marketplace\WebhookData;
use Carbon\CarbonImmutable;

class BuildStatusTimeline
{
    /**
     * @param  list<WebhookData>  $events  Ordered by snapshot version.
     * @return list<array{status: string, at: CarbonImmutable}>
     */
    public function build(array $events): array
    {
        $history = [];

        foreach ($events as $event) {
            $snapshot = $event->snapshot();

            if ($history === []) {
                $history[] = ['status' => 'created', 'at' => $snapshot->date('created_at') ?? $snapshot->updatedAt()];
            }

            $last = $history[array_key_last($history)];
            $paidAt = $snapshot->date('paid_at');

            // A full shipped/cancelled snapshot can supply a missing payment transition.
            if ($last['status'] === 'created' && $paidAt !== null && $paidAt >= $last['at'] && $snapshot->status() !== 'created') {
                $history[] = ['status' => 'paid', 'at' => $paidAt];
                $last = $history[array_key_last($history)];
            }

            if ($last['status'] === $snapshot->status()) {
                continue;
            }

            $at = $snapshot->updatedAt();

            if ($snapshot->status() === 'cancelled') {
                $cancelledAt = $snapshot->date('cancelled_at');

                if ($cancelledAt !== null && $cancelledAt >= $last['at']) {
                    $at = $cancelledAt;
                }
            }

            $history[] = ['status' => $snapshot->status(), 'at' => $at];
        }

        return $history;
    }
}
