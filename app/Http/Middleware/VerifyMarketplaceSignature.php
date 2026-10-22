<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMarketplaceSignature
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('marketplace.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            abort(503, 'Webhook secret is not configured.');
        }

        if (! $request->isJson()) {
            abort(415, 'Expected application/json.');
        }

        $timestamp = $request->header('X-Webhook-Timestamp', '');
        $signature = $request->header('X-Webhook-Signature', '');

        if (! preg_match('/\A[0-9]{1,12}\z/', $timestamp)) {
            abort(401, 'Invalid webhook timestamp.');
        }

        if (abs(now()->getTimestamp() - (int) $timestamp) > config('marketplace.timestamp_tolerance')) {
            abort(401, 'Webhook timestamp is outside the allowed window.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
