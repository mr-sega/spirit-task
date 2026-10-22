<?php

namespace Tests\Feature\OrderNotifications;

use App\Jobs\SendOrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

class NotificationSchedulingTest extends NotificationTestCase
{
    public function test_webhook_writes_outbox_and_database_job_without_sending_mail(): void
    {
        $this->deliver($this->fixture('paid'))->assertNoContent();
        $this->assertDatabaseHas('order_notifications', ['type' => 'paid', 'state' => 'pending']);
        $this->assertDatabaseCount('jobs', 1);
        Mail::assertNothingSent();
    }

    public function test_duplicate_events_and_same_status_updates_schedule_once(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $this->deliver($event)->assertNoContent();
        $event = $this->updateEvent($event, 'evt-more-details', 'paid', '2026-10-21T11:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->assertDatabaseCount('order_notifications', 1);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_created_and_delivered_do_not_schedule_mail(): void
    {
        $event = $this->fixture('created');
        $this->deliver($event)->assertNoContent();
        $event = $this->updateEvent($event, 'evt-delivered', 'delivered', '2026-10-22T11:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->assertDatabaseCount('order_notifications', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_outdated_payment_does_not_schedule_after_shipping(): void
    {
        $this->deliver($this->fixture('shipped'))->assertNoContent();
        $this->deliver($this->fixture('paid'))->assertNoContent();
        $this->assertDatabaseCount('order_notifications', 1);
        $this->assertDatabaseHas('order_notifications', ['type' => 'shipped']);
    }

    public function test_queue_failure_rolls_back_the_event_order_and_notification(): void
    {
        Queue::shouldReceive('connection')->with('database')->andReturnSelf();
        Queue::shouldReceive('push')->withArgs(fn ($job) => $job instanceof SendOrderNotification)
            ->andThrow(new RuntimeException('Queue unavailable'));

        $this->deliver($this->fixture('paid'))->assertServerError();
        $this->assertDatabaseCount('marketplace_events', 0);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_notifications', 0);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_rolling_back_after_queue_insertion_removes_both_job_and_outbox(): void
    {
        try {
            DB::transaction(function (): void {
                $this->deliver($this->fixture('paid'))->assertNoContent();
                $this->assertDatabaseCount('jobs', 1);
                throw new RuntimeException('Rollback after enqueue');
            });
        } catch (RuntimeException) {
            $this->assertDatabaseCount('jobs', 0);
            $this->assertDatabaseCount('order_notifications', 0);
            $this->assertDatabaseCount('orders', 0);
        }
    }
}
