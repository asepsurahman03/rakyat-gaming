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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 50)->index();
            $table->text('payload');
            $table->string('signature')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('response')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('is_valid')->default(false);
            $table->string('order_id')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
