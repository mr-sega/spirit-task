<?php

namespace App\Http\Controllers\Api;

use App\Actions\Orders\ListOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\ListOrdersRequest;
use App\Http\Resources\Orders\OrderListResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListOrdersController extends Controller
{
    public function __invoke(ListOrdersRequest $request, ListOrders $list): AnonymousResourceCollection
    {
        $orders = $list->handle(
            $request->validated('status'),
            $request->validated('email'),
            $request->integer('per_page', 20),
            $request->integer('page', 1),
        );

        return OrderListResource::collection($orders);
    }
}
