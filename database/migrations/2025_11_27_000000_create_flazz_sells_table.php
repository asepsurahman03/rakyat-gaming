<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flazz_sells', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Event name
            $table->text('description')->nullable(); // Event description
            $table->string('banner')->nullable(); // Event banner image
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flazz_sells');
    }
};
