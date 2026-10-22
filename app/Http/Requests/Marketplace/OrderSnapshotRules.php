<?php

namespace App\Http\Requests\Marketplace;

use App\Enums\OrderStatus;
use App\Rules\OffsetDateTime;
use Illuminate\Validation\Rule;

class OrderSnapshotRules
{
    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $amount = ['required', 'string', 'regex:/\A\d{1,12}\.\d{2}\z/'];
        $date = ['required', new OffsetDateTime];
        $nullableDate = ['present', 'nullable', new OffsetDateTime];

        return [
            'data' => ['required', 'array'],
            'data.order' => ['required', 'array'],
            'data.order.id' => ['required', 'string', 'max:255'],
            'data.order.number' => ['required', 'string', 'max:255'],
            'data.order.status' => ['required', Rule::enum(OrderStatus::class)],
            'data.order.currency' => ['required', 'string', 'regex:/\A[A-Z]{3}\z/'],
            'data.order.customer' => ['required', 'array'],
            'data.order.customer.email' => ['required', 'string', 'email:rfc', 'max:255'],
            'data.order.customer.name' => ['required', 'string', 'max:255'],
            'data.order.customer.phone' => ['present', 'nullable', 'string', 'max:255'],
            'data.order.items' => ['required', 'array', 'list', 'min:1', 'max:1000'],
            'data.order.items.*' => ['required', 'array'],
            'data.order.items.*.sku' => ['required', 'string', 'max:255'],
            'data.order.items.*.name' => ['required', 'string', 'max:255'],
            'data.order.items.*.quantity' => ['required', 'integer:strict', 'min:1', 'max:1000000'],
            'data.order.items.*.price' => $amount,
            'data.order.delivery_price' => $amount,
            'data.order.discount' => $amount,
            'data.order.total' => $amount,
            'data.order.shipment' => ['present', 'nullable', 'array', 'required_array_keys:carrier,tracking_number,tracking_url'],
            'data.order.shipment.carrier' => ['required_with:data.order.shipment', 'string', 'max:255'],
            'data.order.shipment.tracking_number' => ['required_with:data.order.shipment', 'string', 'max:255'],
            'data.order.shipment.tracking_url' => ['required_with:data.order.shipment', 'url:http,https', 'max:2048'],
            'data.order.paid_at' => $nullableDate,
            'data.order.cancelled_at' => $nullableDate,
            'data.order.cancel_reason' => ['present', 'nullable', 'string', 'max:10000'],
            'data.order.created_at' => $date,
            'data.order.updated_at' => $date,
        ];
    }
}
