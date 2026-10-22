<?php

namespace App\Http\Resources\Orders;

use App\Models\OrderItem;
use Brick\Math\BigDecimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'sku' => $this->sku,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => (string) BigDecimal::of($this->price)->multipliedBy($this->quantity)->toScale(2),
        ];
    }
}
