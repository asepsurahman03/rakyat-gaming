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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique()->nullable(); // For guest users
            $table->string('status')->default('active'); // active, closed
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
