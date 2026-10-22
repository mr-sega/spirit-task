<?php

namespace App\Http\Controllers\Api;

use App\Actions\Marketplace\ReceiveMarketplaceEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\ReceiveWebhookRequest;
use Illuminate\Http\Response;

class MarketplaceWebhookController extends Controller
{
    public function __invoke(ReceiveWebhookRequest $request, ReceiveMarketplaceEvent $receive): Response
    {
        $receive->handle($request->event());

        return response()->noContent();
    }
}
