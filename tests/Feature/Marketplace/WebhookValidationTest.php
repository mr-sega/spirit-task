<?php

namespace Tests\Feature\Marketplace;

use PHPUnit\Framework\Attributes\DataProvider;

class WebhookValidationTest extends WebhookTestCase
{
    #[DataProvider('invalidFields')]
    public function test_rejects_invalid_snapshots(string $field, mixed $value): void
    {
        $event = $this->fixture('paid');
        data_set($event, $field, $value);
        $this->deliver($event)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('marketplace_events', 0);
    }

    public static function invalidFields(): array
    {
        return [
            ['data.order.total', 4909.50],
            ['data.order.items.0.price', '1290.0'],
            ['data.order.items.0.quantity', 0],
            ['data.order.items.0.quantity', 1.5],
            ['data.order.items.0.quantity', '3'],
            ['data.order.items', ['named-key' => []]],
            ['data.order.items', []],
            ['data.order.shipment', []],
            ['data.order.customer.email', 'invalid'],
            ['data.order.status', 'unknown'],
            ['data.order.updated_at', '2026-10-21 13:05:47'],
            ['data.order.updated_at', '2026-02-30T13:05:47+03:00'],
            ['data.order.updated_at', '2025-10-21T13:05:47+03:00'],
            ['data.order.paid_at', '2027-10-21T13:05:47+03:00'],
        ];
    }

    public function test_requires_nullable_fields_to_be_present(): void
    {
        $event = $this->fixture('created');
        unset($event['data']['order']['shipment']);
        $this->deliver($event)->assertUnprocessable()->assertJsonValidationErrors('data.order.shipment');
    }

    public function test_accepts_unknown_types_without_requiring_order_schema(): void
    {
        $event = ['id' => 'evt-future', 'type' => 'catalog.changed', 'created_at' => now()->toIso8601String(), 'data' => ['future' => true]];
        $this->deliver($event)->assertNoContent();
        $this->deliver($event)->assertNoContent();
        $this->assertDatabaseCount('marketplace_events', 1);
        $this->assertDatabaseCount('orders', 0);
    }
}
