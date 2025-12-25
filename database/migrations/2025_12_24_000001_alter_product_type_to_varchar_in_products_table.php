<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change product_type from enum to varchar for flexible supplier codes
     */
    public function up(): void
    {
        // For MySQL/MariaDB, we need to use raw SQL to alter ENUM to VARCHAR
        // This is because Laravel's change() method doesn't handle ENUM to VARCHAR well

        // Check database driver
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // MySQL/MariaDB: Alter column directly
            DB::statement("ALTER TABLE products MODIFY COLUMN product_type VARCHAR(50) NOT NULL DEFAULT 'digiflazz'");

            // Add index for better query performance
            Schema::table('products', function (Blueprint $table) {
                $table->index('product_type');
            });
        } else {
            // For other databases (PostgreSQL, SQLite), use standard approach
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_type', 50)->default('digiflazz')->change();
                $table->index('product_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove index
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['product_type']);
        });

        // Revert back to enum
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('digiflazz', 'manual') NOT NULL DEFAULT 'digiflazz'");
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->enum('product_type', ['digiflazz', 'manual'])->default('digiflazz')->change();
            });
        }
    }
};
