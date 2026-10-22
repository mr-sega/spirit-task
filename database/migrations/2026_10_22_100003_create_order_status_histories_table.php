<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('status');
            $table->timestampTz('at');
            $table->unique(['order_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
