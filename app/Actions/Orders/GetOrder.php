<?php

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class GetOrder
{
    public function handle(string $id): Order
    {
        // Keep the snapshot and its relations consistent with concurrent webhooks.
        return DB::transaction(fn (): Order => Order::query()
            ->with(['items', 'history'])
            ->sharedLock()
            ->findOrFail($id));
    }
}
