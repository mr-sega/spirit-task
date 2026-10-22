<?php

use App\Http\Controllers\Api\MarketplaceWebhookController;
use App\Http\Middleware\VerifyMarketplaceSignature;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/marketplace', MarketplaceWebhookController::class)
    ->middleware(VerifyMarketplaceSignature::class);
