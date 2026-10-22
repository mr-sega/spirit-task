<?php

namespace Tests\Feature\Orders;

class ListOrdersTest extends OrdersApiTestCase
{
    public function test_empty_list_has_laravel_pagination_shape(): void
    {
        $this->getJson('/api/orders')->assertOk()
            ->assertExactJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total'],
            ])
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('links.next', null);
    }

    public function test_orders_are_sorted_by_marketplace_creation_time_with_stable_ties(): void
    {
        $this->createOrder('ord-z', overrides: ['created_at' => '2026-10-20T12:00:00+03:00']);
        $this->createOrder('ord-a');
        $this->createOrder('ord-b');

        $response = $this->getJson('/api/orders')->assertOk();
        $this->assertSame(['ord-b', 'ord-a', 'ord-z'], array_column($response->json('data'), 'id'));
        $response->assertJsonPath('data.0.total', '3619.50')
            ->assertJsonPath('data.0.created_at', '2026-10-21T10:02:11+00:00')
            ->assertJsonStructure(['data' => [['id', 'number', 'status', 'customer' => ['email', 'name'], 'total', 'currency', 'created_at', 'updated_at']]])
            ->assertJsonMissingPath('data.0.last_event_id')
            ->assertJsonMissingPath('data.0.items');
    }

    public function test_filters_by_status_and_exact_case_insensitive_email_together(): void
    {
        $this->createOrder('paid-anna', 'paid', ['customer.email' => 'Anna.Smirnova@Example.COM']);
        $this->createOrder('created-anna');
        $this->createOrder('paid-other', 'paid', ['customer.email' => 'other@example.com']);

        $this->getJson('/api/orders?status=paid')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/orders?email=ANNA.SMIRNOVA%40EXAMPLE.COM')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/orders?status=paid&email=anna.smirnova%40example.com')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', 'paid-anna');
        $this->getJson('/api/orders?email=anna')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/api/orders?email=%25')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_pagination_keeps_filters_and_returns_empty_out_of_range_page(): void
    {
        foreach (range(1, 3) as $id) {
            $this->createOrder('ord-'.$id);
        }

        $response = $this->getJson('/api/orders?status=created&per_page=1&page=2')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'ord-2')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.total', 3);

        parse_str(parse_url($response->json('links.next'), PHP_URL_QUERY), $query);
        $this->assertSame(['status' => 'created', 'per_page' => '1', 'page' => '3'], $query);
        $this->getJson('/api/orders?per_page=1&page=4')->assertOk()
            ->assertJsonPath('data', [])->assertJsonPath('meta.total', 3);
    }

    public function test_default_page_size_and_maximum_page_size(): void
    {
        foreach (range(1, 21) as $id) {
            $this->createOrder('order-'.$id);
        }

        $this->getJson('/api/orders')->assertOk()->assertJsonCount(20, 'data')->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/orders?per_page=100')->assertOk()->assertJsonCount(21, 'data')->assertJsonPath('meta.per_page', 100);
    }
}
