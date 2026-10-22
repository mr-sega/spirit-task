<?php

namespace Tests\Feature\OrderNotifications;

use App\Actions\OrderNotifications\ClaimOrderNotification;
use App\Actions\OrderNotifications\DeliverOrderNotification;
use App\Jobs\SendOrderNotification;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Marketplace\WebhookTestCase;

abstract class NotificationTestCase extends WebhookTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    protected function sendNotification(string $type): OrderNotification
    {
        $notification = OrderNotification::query()->where('type', $type)->firstOrFail();
        $job = new SendOrderNotification($notification->id);
        $job->handle(app(ClaimOrderNotification::class), app(DeliverOrderNotification::class));

        return $notification->fresh();
    }

    protected function updateEvent(array $event, string $id, string $status, string $at): array
    {
        $event['id'] = $id;
        $event['type'] = 'order.updated';
        $event['data']['order']['status'] = $status;
        $event['data']['order']['updated_at'] = $at;

        return $event;
    }
}
