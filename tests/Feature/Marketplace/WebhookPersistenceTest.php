<?php

namespace Tests\Feature\Marketplace;

use App\Actions\Marketplace\RebuildOrderHistory;
use App\Models\MarketplaceEvent;
use App\Models\Order;
use RuntimeException;

class WebhookPersistenceTest extends WebhookTestCase
{
    public function test_saves_snapshot_positions_and_marketplace_dates(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $order = Order::query()->findOrFail($event['data']['order']['id']);

        $this->assertSame('paid', $order->status);
        $this->assertSame('4909.50', $order->total);
        $this->assertSame('2026-10-21T10:05:47+00:00', $order->updated_at->toIso8601String());
        $this->assertSame([3, 1], $order->items->pluck('quantity')->all());
        $this->assertSame(['created', 'paid'], $order->history->pluck('status')->all());
        $this->assertSame('2026-10-21T10:05:47+00:00', $order->history->last()->at->toIso8601String());
    }

    public function test_repeated_event_is_idempotent_with_a_new_delivery_timestamp(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $this->travel(1)->minutes();
        $this->deliver($event)->assertNoContent();

        $this->assertDatabaseCount('marketplace_events', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertDatabaseCount('order_status_histories', 2);
    }

    public function test_reused_event_id_with_different_payload_is_rejected(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $event['data']['order']['number'] = 'different';
        $this->deliver($event)->assertConflict();
        $this->assertDatabaseHas('orders', ['number' => 'MH-1048213']);
    }

    public function test_new_snapshot_replaces_items_and_clears_nullable_fields(): void
    {
        $event = $this->fixture('shipped');
        $this->deliver($event)->assertNoContent();
        $event['id'] = 'evt-corrected';
        $event['type'] = 'order.updated';
        $event['data']['order']['updated_at'] = '2026-10-22T07:00:00+00:00';
        $event['data']['order']['status'] = 'paid';
        $event['data']['order']['shipment'] = null;
        $event['data']['order']['customer']['phone'] = null;
        $event['data']['order']['items'] = [$event['data']['order']['items'][0]];
        $event['data']['order']['total'] = '4019.00';

        $this->deliver($event)->assertNoContent();
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseHas('orders', ['status' => 'paid', 'shipment' => null, 'customer_phone' => null]);
    }

    public function test_preserves_signed_strings_without_trimming(): void
    {
        $event = $this->fixture('created');
        $event['data']['order']['customer']['name'] = '  Anna  ';
        $event['data']['order']['customer']['phone'] = '';
        $this->deliver($event)->assertNoContent();
        $this->assertDatabaseHas('orders', ['customer_name' => '  Anna  ', 'customer_phone' => '']);
    }

    public function test_rolls_back_everything_and_allows_retry_after_failure(): void
    {
        $event = $this->fixture('paid');
        $this->mock(RebuildOrderHistory::class)->shouldReceive('handle')->once()->andThrow(new RuntimeException('Simulated failure'));
        $this->deliver($event)->assertServerError();
        $this->assertDatabaseCount('marketplace_events', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $this->app->forgetInstance(RebuildOrderHistory::class);
        $this->deliver($event)->assertNoContent();
        $this->assertSame(1, MarketplaceEvent::query()->count());
    }
}
