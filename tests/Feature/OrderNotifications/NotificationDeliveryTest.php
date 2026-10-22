<?php

namespace Tests\Feature\OrderNotifications;

use App\Enums\OrderNotificationState;
use App\Mail\Orders\OrderCancelledMail;
use App\Mail\Orders\OrderPaidMail;
use App\Mail\Orders\OrderShippedMail;
use Illuminate\Support\Facades\Mail;

class NotificationDeliveryTest extends NotificationTestCase
{
    public function test_paid_mail_has_customer_number_items_and_total_and_is_sent_once(): void
    {
        $this->deliver($this->fixture('paid'))->assertNoContent();
        $this->assertSame(OrderNotificationState::Sent, $this->sendNotification('paid')->state);
        $this->sendNotification('paid');
        Mail::assertSentCount(1);
        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail): bool {
            $mail->assertHasSubject('Заказ №MH-1048213 оплачен');
            $mail->assertSeeInText('Футболка базовая, чёрная, M');
            $mail->assertSeeInText('3 × 1290.00 RUB');
            $mail->assertSeeInText('Бейсболка "Classic" / синяя');
            $mail->assertSeeInText('Итого: 4909.50 RUB');

            return $mail->hasTo('anna.smirnova@example.com');
        });
    }

    public function test_shipped_mail_has_carrier_tracking_number_and_url(): void
    {
        $this->deliver($this->fixture('shipped'))->assertNoContent();
        $this->sendNotification('shipped');
        Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail): bool {
            $mail->assertHasSubject('Заказ №MH-1048213 передан в доставку');
            $mail->assertSeeInText('СДЭК');
            $mail->assertSeeInText('1098765432');
            $mail->assertSeeInText('https://www.cdek.ru/ru/tracking?order_id=1098765432');

            return true;
        });
    }

    public function test_paid_cancellation_includes_refund_promise(): void
    {
        $this->deliver($this->fixture('cancelled'))->assertNoContent();
        $this->sendNotification('cancelled');
        Mail::assertSent(OrderCancelledMail::class, function (OrderCancelledMail $mail): bool {
            $mail->assertHasSubject('Заказ №MH-1048377 отменён');
            $mail->assertSeeInText('Покупатель отказался от заказа');
            $mail->assertSeeInText('Деньги вернутся в течение 10 дней.');

            return $mail->hasTo('i.lebedev@example.org');
        });
    }

    public function test_unpaid_cancellation_does_not_promise_a_refund(): void
    {
        $event = $this->fixture('cancelled');
        $event['data']['order']['paid_at'] = null;
        $this->deliver($event)->assertNoContent();
        $this->sendNotification('cancelled');
        Mail::assertSent(OrderCancelledMail::class, function (OrderCancelledMail $mail): bool {
            $mail->assertDontSeeInText('Деньги вернутся');

            return true;
        });
    }

    public function test_waiting_mail_uses_updated_customer_and_order_contents(): void
    {
        $event = $this->fixture('paid');
        $this->deliver($event)->assertNoContent();
        $event = $this->updateEvent($event, 'evt-updated', 'paid', '2026-10-21T11:00:00+00:00');
        $event['data']['order']['customer']['email'] = 'new@example.test';
        $event['data']['order']['total'] = '123.45';
        $event['data']['order']['items'][0]['name'] = 'Updated & corrected';
        $this->deliver($event)->assertNoContent();
        $this->sendNotification('paid');
        Mail::assertSent(OrderPaidMail::class, function (OrderPaidMail $mail): bool {
            $mail->assertSeeInText('Updated & corrected');
            $mail->assertSeeInText('123.45 RUB');

            return $mail->hasTo('new@example.test');
        });
    }
}
