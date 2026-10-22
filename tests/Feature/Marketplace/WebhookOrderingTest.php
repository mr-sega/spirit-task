<?php

namespace Tests\Feature\Marketplace;

use App\Models\Order;

class WebhookOrderingTest extends WebhookTestCase
{
    public function test_reverse_delivery_keeps_latest_snapshot_and_chronological_history(): void
    {
        foreach (['shipped', 'paid', 'updated', 'created'] as $name) {
            $this->deliver($this->fixture($name))->assertNoContent();
        }

        $order = Order::query()->firstOrFail();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('4909.50', $order->total);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame(['created', 'paid', 'shipped'], $order->history->pluck('status')->all());
        $this->assertSame([
            '2026-10-21T10:02:11+00:00',
            '2026-10-21T10:05:47+00:00',
            '2026-10-22T06:40:03+00:00',
        ], $order->history->map(fn ($transition) => $transition->at->toIso8601String())->all());
    }

    public function test_updates_without_a_status_change_do_not_add_transitions(): void
    {
        $this->deliver($this->fixture('created'))->assertNoContent();
        $this->deliver($this->fixture('updated'))->assertNoContent();
        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('orders', ['total' => '4909.50']);
    }

    public function test_accepts_newer_manual_status_correction_and_preserves_returned_status(): void
    {
        $event = $this->fixture('shipped');
        $this->deliver($event)->assertNoContent();
        $event['id'] = 'evt-delivered';
        $event['type'] = 'order.delivered';
        $event['data']['order']['status'] = 'delivered';
        $event['data']['order']['updated_at'] = '2026-10-22T08:00:00+00:00';
        $this->deliver($event)->assertNoContent();
        $event['id'] = 'evt-support-correction';
        $event['type'] = 'order.updated';
        $event['data']['order']['status'] = 'shipped';
        $event['data']['order']['updated_at'] = '2026-10-22T09:00:00+00:00';
        $this->deliver($event)->assertNoContent();

        $order = Order::query()->firstOrFail();
        $this->assertSame('shipped', $order->status);
        $this->assertSame(['created', 'paid', 'shipped', 'delivered', 'shipped'], $order->history->pluck('status')->all());
    }

    public function test_equal_snapshot_times_use_event_time_then_id_independent_of_arrival(): void
    {
        $newer = $this->fixture('paid');
        $newer['id'] = 'evt-z';
        $newer['data']['order']['number'] = 'winner';
        $this->deliver($newer)->assertNoContent();
        $older = $newer;
        $older['id'] = 'evt-a';
        $older['data']['order']['number'] = 'loser';
        $this->deliver($older)->assertNoContent();
        $this->assertDatabaseHas('orders', ['number' => 'winner']);

        $older['id'] = 'evt-later';
        $older['created_at'] = '2026-10-21T10:05:49+00:00';
        $older['data']['order']['number'] = 'later-event';
        $this->deliver($older)->assertNoContent();
        $this->assertDatabaseHas('orders', ['number' => 'later-event']);
        $this->assertDatabaseCount('order_status_histories', 2);
    }

    public function test_cancellation_without_previous_events_recovers_payment_history(): void
    {
        $this->deliver($this->fixture('cancelled'))->assertNoContent();
        $order = Order::query()->firstOrFail();
        $this->assertSame(['created', 'paid', 'cancelled'], $order->history->pluck('status')->all());
        $this->assertSame('2026-10-21T17:47:15+00:00', $order->history->last()->at->toIso8601String());
    }
}
