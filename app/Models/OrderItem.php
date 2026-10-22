<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/** @property string $price */
#[Unguarded]
class OrderItem extends Model
{
    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'quantity' => 'integer'];
    }
}
