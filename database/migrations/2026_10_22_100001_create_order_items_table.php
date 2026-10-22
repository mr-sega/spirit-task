<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('sku');
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 14, 2);
            $table->unique(['order_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
