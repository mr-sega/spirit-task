<?php

// Run only against the dedicated PostgreSQL database documented in README.md.
use App\Models\MarketplaceEvent;
use App\Models\Order;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
});

if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'spirit_webhooks_test') {
    throw new RuntimeException('Use the dedicated spirit_webhooks_test PostgreSQL database.');
}

if (! function_exists('pcntl_fork')) {
    throw new RuntimeException('The pcntl extension is required.');
}

Artisan::call('migrate', ['--force' => true]);
config(['marketplace.webhook_secret' => 'concurrency-test-secret']);
$prefix = 'concurrent-'.bin2hex(random_bytes(8));
$requests = [];

foreach (['created', 'updated', 'paid', 'shipped'] as $name) {
    $event = json_decode(file_get_contents(__DIR__.'/../../task/examples/order-'.$name.'.json'), true, flags: JSON_THROW_ON_ERROR);
    $event['id'] = $prefix.'-'.$name;
    $event['data']['order']['id'] = $prefix;
    $body = json_encode($event, JSON_THROW_ON_ERROR);

    for ($copy = 0; $copy < 4; $copy++) {
        $requests[] = [$event['id'], $body];
    }
}

shuffle($requests);
DB::disconnect();
$children = [];
$startAt = microtime(true) + 0.5;

foreach ($requests as [$id, $body]) {
    $pid = pcntl_fork();

    if ($pid === -1) {
        throw new RuntimeException('Cannot fork a webhook client.');
    }

    if ($pid > 0) {
        $children[] = $pid;

        continue;
    }

    while (microtime(true) < $startAt) {
        usleep(1000);
    }

    $timestamp = (string) time();
    $request = Request::create('/api/webhooks/marketplace', 'POST', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_WEBHOOK_ID' => $id,
        'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
        'HTTP_X_WEBHOOK_SIGNATURE' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, 'concurrency-test-secret'),
    ], content: $body);
    $response = $app->make(Kernel::class)->handle($request);

    if ($response->getStatusCode() !== 204) {
        fwrite(STDERR, $response->getStatusCode().' '.$response->getContent().PHP_EOL);
        exit(1);
    }

    exit(0);
}

$failed = false;

foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);

    if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
        $failed = true;
    }
}

$order = Order::query()->findOrFail($prefix);

if ($failed || $order->status !== 'shipped' || $order->items()->count() !== 2) {
    throw new RuntimeException('Concurrent deliveries produced an invalid snapshot.');
}

if (MarketplaceEvent::query()->where('order_id', $prefix)->count() !== 4) {
    throw new RuntimeException('Event deduplication failed.');
}

if ($order->history()->pluck('status')->all() !== ['created', 'paid', 'shipped']) {
    throw new RuntimeException('Concurrent deliveries produced an invalid history.');
}

echo 'PASS: 16 parallel deliveries, 4 unique events, one current order and chronological history.'.PHP_EOL;
