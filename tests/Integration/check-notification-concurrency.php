<?php

use App\Actions\Marketplace\ReceiveMarketplaceEvent;
use App\Actions\OrderNotifications\ClaimOrderNotification;
use App\Actions\OrderNotifications\DeliverOrderNotification;
use App\Data\Marketplace\WebhookData;
use App\Enums\OrderNotificationState;
use App\Jobs\SendOrderNotification;
use App\Models\OrderNotification;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
});

if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'spirit_webhooks_test') {
    throw new RuntimeException('Use the dedicated spirit_webhooks_test PostgreSQL database.');
}

if (config('mail.default') !== 'smtp' || config('mail.mailers.smtp.host') !== 'mailpit') {
    throw new RuntimeException('This integration check sends only to the local Mailpit service.');
}

Artisan::call('migrate', ['--force' => true]);
$prefix = 'mail-concurrent-'.bin2hex(random_bytes(8));
$event = json_decode(file_get_contents(__DIR__.'/../../task/examples/order-paid.json'), true, flags: JSON_THROW_ON_ERROR);
$event['id'] = $prefix;
$event['data']['order']['id'] = $prefix;
$event['data']['order']['number'] = $prefix;
$event['data']['order']['customer']['email'] = $prefix.'@example.test';
app(ReceiveMarketplaceEvent::class)->handle(new WebhookData($event));
$id = OrderNotification::query()->where('order_id', $prefix)->sole()->id;
DB::disconnect();
$children = [];
$startAt = microtime(true) + 0.5;

for ($index = 0; $index < 8; $index++) {
    $pid = pcntl_fork();

    if ($pid === -1) {
        throw new RuntimeException('Cannot fork a notification worker.');
    }

    if ($pid > 0) {
        $children[] = $pid;

        continue;
    }

    while (microtime(true) < $startAt) {
        usleep(1000);
    }

    (new SendOrderNotification($id))->handle(app(ClaimOrderNotification::class), app(DeliverOrderNotification::class));
    exit(0);
}

foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);

    if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
        throw new RuntimeException('A concurrent notification worker failed.');
    }
}

if (OrderNotification::query()->findOrFail($id)->state !== OrderNotificationState::Sent) {
    throw new RuntimeException('Notification was not marked as sent.');
}

// Read only the isolated local Mailpit inbox to verify actual SMTP deliveries.
$messages = json_decode(file_get_contents('http://mailpit:8025/api/v1/messages'), true, flags: JSON_THROW_ON_ERROR);
$matches = array_filter($messages['messages'], fn (array $message): bool => $message['Subject'] === 'Заказ №'.$prefix.' оплачен');

if (count($matches) !== 1) {
    throw new RuntimeException('Expected exactly one Mailpit message for eight workers.');
}

echo 'PASS: eight concurrent workers produced exactly one SMTP message: '.$prefix.PHP_EOL;
