<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

class ShowOrderTest extends OrdersApiTestCase
{
    public function test_detail_matches_the_snapshot_and_includes_calculated_subtotals(): void
    {
        $snapshot = $this->createOrder('ord-detail', 'shipped');
        $response = $this->getJson('/api/orders/ord-detail')->assertOk();
        $expected = $snapshot;
        $expected['created_at'] = '2026-10-21T10:02:11+00:00';
        $expected['updated_at'] = '2026-10-22T06:40:03+00:00';
        $expected['paid_at'] = '2026-10-21T10:05:47+00:00';
        $expected['items'][0]['subtotal'] = '3870.00';
        $expected['items'][1]['subtotal'] = '890.50';
        $expected['history'] = [
            ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
            ['status' => 'paid', 'at' => '2026-10-21T10:05:47+00:00'],
            ['status' => 'shipped', 'at' => '2026-10-22T06:40:03+00:00'],
        ];

        $response->assertExactJson(['data' => $expected]);
    }

    public function test_null_fields_are_present_and_internal_fields_are_hidden(): void
    {
        $this->createOrder('ord-null', overrides: ['customer.phone' => null]);
        $response = $this->getJson('/api/orders/ord-null')->assertOk()
            ->assertJsonStructure(['data' => ['shipment', 'paid_at', 'cancelled_at', 'cancel_reason', 'customer' => ['phone']]])
            ->assertJsonPath('data.shipment', null)
            ->assertJsonPath('data.paid_at', null)
            ->assertJsonPath('data.cancelled_at', null)
            ->assertJsonPath('data.cancel_reason', null)
            ->assertJsonPath('data.customer.phone', null)
            ->assertJsonMissingPath('data.last_event_created_at')
            ->assertJsonMissingPath('data.items.0.order_id');

        $this->assertSame(['created'], array_column($response->json('data.history'), 'status'));
    }

    public function test_cancelled_order_keeps_reason_and_payment_dates(): void
    {
        $this->createOrder('ord-cancelled', 'cancelled');
        $this->getJson('/api/orders/ord-cancelled')->assertOk()
            ->assertJsonPath('data.cancel_reason', 'Покупатель отказался от заказа')
            ->assertJsonPath('data.paid_at', '2026-10-21T15:11:02+00:00')
            ->assertJsonPath('data.cancelled_at', '2026-10-21T17:47:15+00:00');
    }

    public function test_subtotal_uses_exact_decimal_arithmetic_even_above_integer_range(): void
    {
        $this->createOrder('ord-money', overrides: [
            'items' => [['sku' => 'BIG', 'name' => 'Large amount', 'quantity' => 1000000, 'price' => '999999999999.99']],
        ]);
        $this->getJson('/api/orders/ord-money')->assertOk()
            ->assertJsonPath('data.items.0.price', '999999999999.99')
            ->assertJsonPath('data.items.0.subtotal', '999999999999990000.00');
    }

    public function test_unknown_marketplace_id_returns_json_404(): void
    {
        $this->get('/api/orders/missing-order')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_detail_loads_relations_without_lazy_queries(): void
    {
        $this->createOrder('ord-loaded');
        Model::preventLazyLoading();
        Order::preventLazyLoading();

        try {
            $this->getJson('/api/orders/ord-loaded')->assertOk()->assertJsonCount(2, 'data.items');
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
