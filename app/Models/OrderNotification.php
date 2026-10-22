<?php

namespace App\Models;

use App\Enums\OrderNotificationState;
use App\Enums\OrderNotificationType;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $order_id
 * @property OrderNotificationType $type
 * @property OrderNotificationState $state
 * @property string|null $attempt_id
 */
#[Unguarded]
class OrderNotification extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => OrderNotificationType::class,
            'state' => OrderNotificationState::class,
            'claimed_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
