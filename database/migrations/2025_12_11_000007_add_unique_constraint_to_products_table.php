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
        Schema::table('products', function (Blueprint $table) {
            // Add unique constraint for Digiflazz products (game_id + supplier_product_id)
            // Prevents duplicate products from the same supplier in the same game
            $table->unique(['game_id', 'supplier_product_id'], 'unique_game_supplier_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('unique_game_supplier_product');
        });
    }
};
