<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $status
 * @property CarbonImmutable $updated_at
 * @property CarbonImmutable $last_event_created_at
 * @property string $last_event_id
 */
#[Unguarded]
class Order extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'shipment' => 'array',
            'delivery_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'last_event_created_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('position');
    }
}
