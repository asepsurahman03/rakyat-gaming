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
        Schema::create('flazz_sell_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flazz_sell_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_percentage', 5, 2); // Each product has own discount
            $table->integer('quota')->nullable(); // Each product has own quota
            $table->integer('sold_count')->default(0); // Track sales per product
            $table->timestamps();

            // Prevent duplicate products in same event
            $table->unique(['flazz_sell_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flazz_sell_products');
    }
};
