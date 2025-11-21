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
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100)->index(); // kode produk dari Digiflazz
            $table->string('category', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('product_name', 150)->nullable();
            $table->decimal('buyer_price', 12, 2)->default(0); // harga modal
            $table->enum('status', ['active','inactive'])->default('active');
            $table->json('raw_json')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id','code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
