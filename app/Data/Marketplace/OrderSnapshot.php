<?php

namespace App\Data\Marketplace;

use Carbon\CarbonImmutable;

class OrderSnapshot
{
    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data) {}

    public function id(): string
    {
        return $this->data['id'];
    }

    public function status(): string
    {
        return $this->data['status'];
    }

    public function date(string $field): ?CarbonImmutable
    {
        if ($this->data[$field] === null) {
            return null;
        }

        return CarbonImmutable::parse($this->data[$field])->utc();
    }

    public function updatedAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->data['updated_at'])->utc();
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        return [
            'number' => $this->data['number'],
            'status' => $this->status(),
            'currency' => $this->data['currency'],
            'customer_email' => $this->data['customer']['email'],
            'customer_name' => $this->data['customer']['name'],
            'customer_phone' => $this->data['customer']['phone'],
            'delivery_price' => $this->data['delivery_price'],
            'discount' => $this->data['discount'],
            'total' => $this->data['total'],
            'shipment' => $this->data['shipment'],
            'paid_at' => $this->date('paid_at'),
            'cancelled_at' => $this->date('cancelled_at'),
            'cancel_reason' => $this->data['cancel_reason'],
            'created_at' => $this->date('created_at'),
            'updated_at' => $this->updatedAt(),
        ];
    }

    /** @return list<array{sku: string, name: string, quantity: int, price: string}> */
    public function items(): array
    {
        return $this->data['items'];
    }
}
