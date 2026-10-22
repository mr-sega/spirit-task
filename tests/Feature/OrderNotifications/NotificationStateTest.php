<?php

namespace Tests\Feature\OrderNotifications;

use App\Actions\OrderNotifications\ClaimOrderNotification;
use App\Actions\OrderNotifications\DeliverOrderNotification;
use App\Enums\OrderNotificationState;
use App\Mail\Orders\OrderPaidMail;
use App\Models\OrderNotification;
use Illuminate\Support\Facades\Mail;

class NotificationStateTest extends NotificationTestCase
{
    public function test_cancellation_suppresses_waiting_payment_mail(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $event = $this->updateEvent($event, 'evt-cancelled', 'cancelled', '2026-10-21T11:00:00+00:00');
        $event['data']['order']['cancelled_at'] = $event['data']['order']['updated_at'];
        $event['data']['order']['cancel_reason'] = 'Changed mind';
        $this->deliver($event)->assertNoContent();
        $this->assertSame(OrderNotificationState::Suppressed, $this->sendNotification('paid')->state);
        $this->sendNotification('cancelled');
        Mail::assertNotSent(OrderPaidMail::class);
        Mail::assertSentCount(1);
    }

    public function test_delivery_suppresses_waiting_shipping_mail(): void
    {
        $event = $this->fixture('shipped');
        $this->deliver($event)->assertNoContent();
        $event = $this->updateEvent($event, 'evt-delivered', 'delivered', '2026-10-22T11:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->assertSame(OrderNotificationState::Suppressed, $this->sendNotification('shipped')->state);
        Mail::assertNothingSent();
    }

    public function test_rechecks_current_state_after_claim_before_sending(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $notification = OrderNotification::query()->firstOrFail();
        $attempt = app(ClaimOrderNotification::class)->handle($notification->id);
        $this->deliver($this->fixture('shipped'))->assertNoContent();
        app(DeliverOrderNotification::class)->handle($notification->id, $attempt);
        $this->assertSame(OrderNotificationState::Suppressed, $notification->fresh()->state);
        Mail::assertNothingSent();
    }

    public function test_suppressed_mail_can_become_relevant_again_but_sent_mail_cannot_repeat(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $this->deliver($this->fixture('shipped'))->assertNoContent();
        $this->sendNotification('paid');
        $event = $this->updateEvent($event, 'evt-correction', 'paid', '2026-10-22T11:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->sendNotification('paid');
        $event = $this->updateEvent($event, 'evt-another-update', 'paid', '2026-10-22T12:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->sendNotification('paid');
        Mail::assertSentCount(1);
    }

    public function test_shipping_without_details_waits_for_a_corrected_snapshot(): void
    {
        $event = $this->fixture('shipped');
        $event['data']['order']['shipment'] = null;
        $this->deliver($event)->assertNoContent();
        $this->assertSame(OrderNotificationState::Suppressed, $this->sendNotification('shipped')->state);
        Mail::assertNothingSent();
        $event = $this->updateEvent($this->fixture('shipped'), 'evt-tracking', 'shipped', '2026-10-22T11:00:00+00:00');
        $this->deliver($event)->assertNoContent();
        $this->assertSame(OrderNotificationState::Sent, $this->sendNotification('shipped')->state);
        Mail::assertSentCount(1);
    }
}
