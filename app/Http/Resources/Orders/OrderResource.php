<?php

namespace App\Http\Resources\Orders;

use Illuminate\Http\Request;

class OrderResource extends OrderListResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'customer' => [
                'email' => $this->customer_email,
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'delivery_price' => $this->delivery_price,
            'discount' => $this->discount,
            'shipment' => $this->shipment,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'history' => OrderHistoryResource::collection($this->whenLoaded('history')),
        ];
    }
}
