<?php

namespace App\Http\Requests\Marketplace;

use App\Data\Marketplace\WebhookData;
use App\Enums\MarketplaceEventType;
use App\Rules\OffsetDateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReceiveWebhookRequest extends FormRequest
{
    /** @return array<string, array<mixed>> */
    public function rules(OrderSnapshotRules $snapshotRules): array
    {
        $rules = [
            'id' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'created_at' => ['required', new OffsetDateTime],
        ];

        $type = $this->json('type');

        if (! is_string($type) || MarketplaceEventType::tryFrom($type) === null) {
            return $rules;
        }

        return [...$rules, ...$snapshotRules->rules()];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [new ValidateSnapshotDates, function (Validator $validator): void {
            if ($this->header('X-Webhook-Id') !== $this->json('id')) {
                $validator->errors()->add('id', 'X-Webhook-Id must match the event id.');
            }
        }];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return $this->json()->all();
    }

    public function event(): WebhookData
    {
        // Keep the full JSON for auditing and detecting reused IDs with different data.
        return new WebhookData($this->json()->all());
    }
}
