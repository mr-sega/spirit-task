<?php

namespace Tests\Feature\OrderNotifications;

use App\Actions\OrderNotifications\ClaimOrderNotification;
use App\Enums\OrderNotificationState;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class NotificationFailureTest extends NotificationTestCase
{
    public function test_ambiguous_transport_error_is_not_retried(): void
    {
        $this->deliver($this->fixture('paid'))->assertNoContent();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Transport disconnected after acceptance'));
        $notification = $this->sendNotification('paid');
        $this->assertSame(OrderNotificationState::Uncertain, $notification->state);
        $this->assertSame(RuntimeException::class, $notification->last_error);
        $this->sendNotification('paid');
    }

    public function test_a_crash_after_claim_does_not_allow_another_smtp_attempt(): void
    {
        $this->deliver($this->fixture('paid'))->assertNoContent();
        $notification = OrderNotification::query()->firstOrFail();
        $claim = app(ClaimOrderNotification::class);
        $this->assertNotNull($claim->handle($notification->id));
        $this->assertNull($claim->handle($notification->id));
        $this->sendNotification('paid');
        Mail::assertNothingSent();
        $this->assertSame(OrderNotificationState::Sending, $notification->fresh()->state);
    }

    public function test_mail_accepted_before_database_failure_is_not_sent_again(): void
    {
        $this->deliver($this->fixture('paid'))->assertNoContent();
        OrderNotification::saving(function (OrderNotification $notification): void {
            if ($notification->state === OrderNotificationState::Sent) {
                throw new RuntimeException('Database write failed after SMTP');
            }
        });

        try {
            $this->assertSame(OrderNotificationState::Uncertain, $this->sendNotification('paid')->state);
            $this->sendNotification('paid');
            Mail::assertSentCount(1);
        } finally {
            OrderNotification::flushEventListeners();
        }
    }
}
