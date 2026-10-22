<?php

namespace App\Jobs;

use App\Actions\OrderNotifications\ClaimOrderNotification;
use App\Actions\OrderNotifications\DeliverOrderNotification;
use App\Enums\OrderNotificationState;
use App\Models\OrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendOrderNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $notificationId)
    {
        $this->onConnection('database');
        $this->beforeCommit();
    }

    public function handle(ClaimOrderNotification $claim, DeliverOrderNotification $deliver): void
    {
        $attempt = $claim->handle($this->notificationId);

        if ($attempt === null) {
            return;
        }

        try {
            $deliver->handle($this->notificationId, $attempt);
        } catch (Throwable $error) {
            OrderNotification::query()->whereKey($this->notificationId)
                ->where('attempt_id', $attempt)
                ->where('state', OrderNotificationState::Sending)
                ->update(['state' => OrderNotificationState::Uncertain, 'last_error' => $error::class]);

            report($error);
        }
    }
}
