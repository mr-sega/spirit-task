<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_events', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('type');
            $table->string('order_id')->nullable()->index();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->json('payload');
            $table->timestampTz('created_at');
            $table->timestampTz('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_events');
    }
};
