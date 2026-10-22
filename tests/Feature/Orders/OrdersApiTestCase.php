<?php

namespace Tests\Feature\Orders;

use Tests\Feature\Marketplace\WebhookTestCase;

abstract class OrdersApiTestCase extends WebhookTestCase
{
    protected function createOrder(string $id, string $fixture = 'created', array $overrides = []): array
    {
        $event = $this->fixture($fixture);
        $event['id'] = 'event-'.$id;
        $event['data']['order']['id'] = $id;

        foreach ($overrides as $key => $value) {
            data_set($event['data']['order'], $key, $value);
        }

        $this->deliver($event)->assertNoContent();

        return $event['data']['order'];
    }
}
