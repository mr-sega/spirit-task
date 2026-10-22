<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\GetOrder;
use App\Http\Controllers\Controller;
use App\Http\Resources\Orders\OrderResource;
use Inertia\Inertia;
use Inertia\Response;

class ShowOrderController extends Controller
{
    public function __invoke(string $id, GetOrder $get): Response
    {
        return Inertia::render('orders/Show', [
            'order' => new OrderResource($get->handle($id)),
        ]);
    }
}
