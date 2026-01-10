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
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->enum('period', ['week', 'month', 'all'])->default('week')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->string('last_item')->nullable();
            $table->timestamp('last_transaction_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('rank')->default(0);
            $table->timestamps();

            // Index untuk performa
            $table->index(['period', 'rank', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboards');
    }
};
