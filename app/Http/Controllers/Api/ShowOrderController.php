<?php

namespace App\Http\Controllers\Api;

use App\Actions\Orders\GetOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\Orders\OrderResource;

class ShowOrderController extends Controller
{
    public function __invoke(string $id, GetOrder $get): OrderResource
    {
        return new OrderResource($get->handle($id));
    }
}
