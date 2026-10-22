<?php

namespace Tests\Feature\Admin;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Orders\OrdersApiTestCase;

class OrdersPagesTest extends OrdersApiTestCase
{
    public function test_guest_can_filter_orders_and_paginate_without_losing_filters(): void
    {
        $this->createOrder('first', overrides: ['customer.email' => 'buyer@example.com']);
        $this->createOrder('second', overrides: ['customer.email' => 'buyer@example.com']);
        $this->createOrder('excluded', 'paid');

        $this->get('/orders?status=created&email=BUYER@example.com&per_page=1')
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('orders/Index')->has('orders.data', 1)
            ->where('orders.meta.total', 2)
            ->where('filters.email', 'BUYER@example.com')
            ->where('orders.links.next', fn ($url) => str_contains($url, 'status=created') && str_contains($url, 'page=2')));
    }

    public function test_guest_can_view_snapshot_items_and_history(): void
    {
        $this->createOrder('detail', 'shipped');
        $this->get('/orders/detail')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('orders/Show')->where('order.data.id', 'detail')
            ->has('order.data.items', 2)->has('order.data.history', 3)
            ->where('order.data.items.0.subtotal', '3870.00')
            ->missing('order.data.last_event_id'));
    }

    public function test_empty_list_is_rendered(): void
    {
        $this->get('/orders')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('orders/Index')->has('orders.data', 0)->where('orders.meta.total', 0));
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $this->from('/orders')->get('/orders?status=unknown&per_page=101')
            ->assertRedirect('/orders')->assertSessionHasErrors(['status', 'per_page']);
    }

    public function test_missing_order_returns_404(): void
    {
        $this->get('/orders/missing')->assertNotFound();
    }
}
