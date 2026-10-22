<?php

use App\Http\Controllers\Api\ListOrdersController;
use App\Http\Controllers\Api\MarketplaceWebhookController;
use App\Http\Controllers\Api\ShowOrderController;
use App\Http\Middleware\VerifyMarketplaceSignature;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/marketplace', MarketplaceWebhookController::class)
    ->middleware(VerifyMarketplaceSignature::class);

Route::get('/orders', ListOrdersController::class);
Route::get('/orders/{id}', ShowOrderController::class);
