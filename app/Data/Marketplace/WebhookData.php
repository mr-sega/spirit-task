<?php

namespace App\Data\Marketplace;

use App\Enums\MarketplaceEventType;
use Carbon\CarbonImmutable;

class WebhookData
{
    /** @param array<string, mixed> $payload */
    public function __construct(public readonly array $payload) {}

    public function id(): string
    {
        return $this->payload['id'];
    }

    public function type(): string
    {
        return $this->payload['type'];
    }

    public function isSupported(): bool
    {
        return MarketplaceEventType::tryFrom($this->type()) !== null;
    }

    public function createdAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->payload['created_at'])->utc();
    }

    public function snapshot(): OrderSnapshot
    {
        return new OrderSnapshot($this->payload['data']['order']);
    }
}
