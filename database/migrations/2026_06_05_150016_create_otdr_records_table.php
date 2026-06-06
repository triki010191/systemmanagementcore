<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §7.3 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otdr_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_core_id')->constrained('fiber_cores')->cascadeOnDelete();
            $table->decimal('total_loss_db', 6, 2)->nullable();
            $table->decimal('distance_km', 8, 3)->nullable();
            $table->json('event_points')->nullable();
            $table->string('trace_file_path')->nullable();
            $table->foreignId('measured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('measured_at')->nullable();
            $table->timestamps();

            $table->index('cable_core_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otdr_records');
    }
};
