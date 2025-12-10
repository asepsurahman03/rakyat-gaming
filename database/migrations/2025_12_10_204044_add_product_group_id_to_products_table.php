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
            $table->foreignId('product_group_id')->nullable()->after('game_id')->constrained('product_groups')->nullOnDelete();
            $table->integer('sort_order')->default(0)->after('description');

            // Indexes for performance
            $table->index('product_group_id');
            $table->index('sort_order');
            $table->index(['product_group_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_group_id']);
            $table->dropIndex(['product_group_id']);
            $table->dropIndex(['sort_order']);
            $table->dropIndex(['product_group_id', 'sort_order']);
            $table->dropColumn(['product_group_id', 'sort_order']);
        });
    }
};
