<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/** @property array<string, mixed> $payload */
#[Unguarded]
class MarketplaceEvent extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
        ];
    }
}
