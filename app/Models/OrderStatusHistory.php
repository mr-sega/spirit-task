<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class OrderStatusHistory extends Model
{
    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['at' => 'immutable_datetime'];
    }
}
