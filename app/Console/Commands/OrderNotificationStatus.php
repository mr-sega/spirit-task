<?php

namespace App\Console\Commands;

use App\Enums\OrderNotificationState;
use App\Models\OrderNotification;
use Illuminate\Console\Command;

class OrderNotificationStatus extends Command
{
    protected $signature = 'orders:notification-status';

    protected $description = 'Show notification counts and attempts requiring manual inspection';

    public function handle(): int
    {
        $rows = [];

        foreach (OrderNotificationState::cases() as $state) {
            $rows[] = [$state->value, OrderNotification::query()->where('state', $state)->count()];
        }

        $this->table(['State', 'Count'], $rows);
        $attempts = OrderNotification::query()
            ->whereIn('state', [OrderNotificationState::Sending, OrderNotificationState::Uncertain])
            ->orderBy('id')->limit(50)
            ->get(['id', 'order_id', 'type', 'state', 'claimed_at', 'last_error']);

        if ($attempts->isEmpty()) {
            return self::SUCCESS;
        }

        $this->warn('Sending may be active or interrupted. Inspect Mailpit/logs before any manual recovery.');
        $this->table(['ID', 'Order', 'Type', 'State', 'Claimed at', 'Error'], $attempts->toArray());

        return self::SUCCESS;
    }
}
