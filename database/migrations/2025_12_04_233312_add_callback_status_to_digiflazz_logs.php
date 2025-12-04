<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change status column from ENUM to VARCHAR to support more statuses
        DB::statement("ALTER TABLE `digiflazz_logs` MODIFY `status` VARCHAR(20) DEFAULT 'sent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM
        DB::statement("ALTER TABLE `digiflazz_logs` MODIFY `status` ENUM('sent','success','failed') DEFAULT 'sent'");
    }
};
