<?php

namespace Tests\Feature\Marketplace;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class WebhookTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['marketplace.webhook_secret' => 'test-secret']);
        $this->travelTo(now()->setDate(2026, 10, 23)->startOfDay());
    }

    protected function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path('task/examples/order-'.$name.'.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    protected function deliver(array $event, array $headers = [], ?int $timestamp = null): TestResponse
    {
        $body = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->deliverBody($body, $event['id'], $headers, $timestamp);
    }

    protected function deliverBody(string $body, string $id, array $headers = [], ?int $timestamp = null): TestResponse
    {
        $timestamp ??= now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        return $this->call('POST', '/api/webhooks/marketplace', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_ID' => $id,
            'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256='.$signature,
            ...$headers,
        ], content: $body);
    }
}
