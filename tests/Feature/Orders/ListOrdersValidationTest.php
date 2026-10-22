<?php

namespace Tests\Feature\Orders;

use PHPUnit\Framework\Attributes\DataProvider;

class ListOrdersValidationTest extends OrdersApiTestCase
{
    #[DataProvider('invalidQueries')]
    public function test_invalid_filters_return_json_validation_errors(string $query, string $field): void
    {
        $this->get('/api/orders?'.$query)->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function invalidQueries(): array
    {
        return [
            ['status=unknown', 'status'],
            ['status[]=paid', 'status'],
            ['email[]=anna', 'email'],
            ['page=0', 'page'],
            ['page=-1', 'page'],
            ['page=1.5', 'page'],
            ['page=abc', 'page'],
            ['page=', 'page'],
            ['page[]=1', 'page'],
            ['per_page=0', 'per_page'],
            ['per_page=101', 'per_page'],
            ['per_page=abc', 'per_page'],
            ['per_page[]=20', 'per_page'],
        ];
    }

    public function test_empty_optional_filters_are_ignored(): void
    {
        $this->createOrder('ord-empty');
        $this->getJson('/api/orders?email=&status=')->assertOk()->assertJsonPath('meta.total', 1);
    }
}
