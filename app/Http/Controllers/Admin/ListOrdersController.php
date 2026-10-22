<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\ListOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Http\Resources\Orders\OrderListResource;
use Inertia\Inertia;
use Inertia\Response;

class ListOrdersController extends Controller
{
    public function __invoke(ListOrdersRequest $request, ListOrders $list): Response
    {
        $orders = $list->handle(
            $request->validated('status'),
            $request->validated('email'),
            $request->integer('per_page', 20),
            $request->integer('page', 1),
        );

        return Inertia::render('orders/Index', [
            'orders' => OrderListResource::collection($orders),
            'filters' => $request->safe()->only(['status', 'email', 'per_page']),
        ]);
    }
}
