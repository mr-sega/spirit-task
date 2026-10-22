<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('number');
            $table->string('status')->index();
            $table->char('currency', 3);
            $table->string('customer_email')->index();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->decimal('delivery_price', 14, 2);
            $table->decimal('discount', 14, 2);
            $table->decimal('total', 14, 2);
            $table->json('shipment')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestampTz('created_at')->index();
            $table->timestampTz('updated_at');
            $table->timestampTz('last_event_created_at');
            $table->string('last_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
