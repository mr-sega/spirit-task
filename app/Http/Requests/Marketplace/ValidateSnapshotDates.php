<?php

namespace App\Http\Requests\Marketplace;

use App\Enums\MarketplaceEventType;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

class ValidateSnapshotDates
{
    public function __invoke(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $data = $validator->getData();

        if (MarketplaceEventType::tryFrom($data['type']) === null) {
            return;
        }

        $order = $data['data']['order'];
        $createdAt = CarbonImmutable::parse($order['created_at']);
        $updatedAt = CarbonImmutable::parse($order['updated_at']);

        if ($updatedAt < $createdAt) {
            $validator->errors()->add('data.order.updated_at', 'Order update cannot precede creation.');
        }

        foreach (['paid_at', 'cancelled_at'] as $field) {
            if ($order[$field] === null) {
                continue;
            }

            $at = CarbonImmutable::parse($order[$field]);

            if ($at < $createdAt || $at > $updatedAt) {
                $validator->errors()->add('data.order.'.$field, 'Transition must fall within the order lifetime.');
            }
        }
    }
}
