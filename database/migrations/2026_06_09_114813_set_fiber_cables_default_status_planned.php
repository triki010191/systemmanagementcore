<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE fiber_cables MODIFY COLUMN status ENUM('active', 'inactive', 'damaged', 'planned') NOT NULL DEFAULT 'planned'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE fiber_cables MODIFY COLUMN status ENUM('active', 'inactive', 'damaged', 'planned') NOT NULL DEFAULT 'active'");
    }
};
