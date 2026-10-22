<?php

namespace App\Actions\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListOrders
{
    /** @return LengthAwarePaginator<int, Order> */
    public function handle(?string $status, ?string $email, int $perPage, int $page): LengthAwarePaginator
    {
        $query = Order::query();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($email !== null) {
            $query->whereRaw('LOWER(customer_email) = ?', [mb_strtolower($email)]);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page)
            ->withQueryString();
    }
}
