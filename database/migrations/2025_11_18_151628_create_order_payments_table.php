<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->constrained()->cascadeOnDelete();
            $table->string('method', 50)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['pending','paid','expired','failed'])->default('pending');
            $table->string('payment_reference', 150)->nullable();
            $table->string('pay_url', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
