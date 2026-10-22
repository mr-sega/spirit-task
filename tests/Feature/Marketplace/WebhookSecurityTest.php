<?php

namespace Tests\Feature\Marketplace;

use Carbon\CarbonImmutable;

class WebhookSecurityTest extends WebhookTestCase
{
    public function test_accepts_the_documented_signature_byte_for_byte(): void
    {
        config(['marketplace.webhook_secret' => 'whsec_test_5f1d8c2a9b']);
        $this->travelTo(CarbonImmutable::createFromTimestamp(1792577149));
        $body = file_get_contents(base_path('task/examples/signature-example-body.json'));
        $event = json_decode($body, true);

        $this->deliverBody($body, $event['id'], [
            'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256=4d4f2d50f98596ab503d29dc96db6fda247e3c482bff130bd97353564cb163f7',
        ])->assertNoContent();
    }

    public function test_rejects_a_body_changed_after_signing(): void
    {
        $event = $this->fixture('created');
        $body = json_encode($event);
        $signature = hash_hmac('sha256', now()->timestamp.'.'.$body, 'test-secret');

        $this->deliverBody($body."\n", $event['id'], [
            'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256='.$signature,
        ])->assertUnauthorized();
        $this->assertDatabaseCount('marketplace_events', 0);
    }

    public function test_rejects_expired_and_future_timestamps_but_accepts_boundary(): void
    {
        $event = $this->fixture('created');
        $this->deliver($event, timestamp: now()->timestamp - 301)->assertUnauthorized();
        $this->deliver($event, timestamp: now()->timestamp + 301)->assertUnauthorized();
        $this->deliver($event, timestamp: now()->timestamp - 300)->assertNoContent();
        $this->deliver($event, timestamp: now()->timestamp + 300)->assertNoContent();
    }

    public function test_requires_matching_event_header(): void
    {
        $this->deliver($this->fixture('created'), ['HTTP_X_WEBHOOK_ID' => 'another-id'])
            ->assertUnprocessable()->assertJsonValidationErrors('id');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_fails_closed_when_secret_is_missing(): void
    {
        config(['marketplace.webhook_secret' => '']);
        $this->deliver($this->fixture('created'))->assertStatus(503);
        $this->assertDatabaseCount('marketplace_events', 0);
    }

    public function test_rejects_unsigned_and_non_json_requests(): void
    {
        $this->postJson('/api/webhooks/marketplace', $this->fixture('created'))->assertUnauthorized();
        $this->deliver($this->fixture('created'), ['CONTENT_TYPE' => 'text/plain'])->assertStatus(415);
    }

    public function test_rejects_malformed_json_and_does_not_use_query_fields(): void
    {
        $this->deliverBody('{', 'evt-invalid')->assertUnprocessable();
        $this->postJson('/api/webhooks/marketplace?id=evt-query', [])->assertUnauthorized();
        $this->assertDatabaseCount('marketplace_events', 0);
    }
}
