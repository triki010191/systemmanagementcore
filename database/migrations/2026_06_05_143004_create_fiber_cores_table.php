<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core fiber individual per kabel — Core Management & traceability ke node.
 *
 * @see docs/migrations/2026_06_05_143004_create_fiber_cores_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiber_cores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_id')->constrained('fiber_cables')->cascadeOnDelete();
            $table->unsignedTinyInteger('core_number');
            $table->unsignedTinyInteger('tube_number');
            $table->foreignId('tube_color_id')->nullable()->constrained('tube_colors')->nullOnDelete();
            $table->enum('status', ['active', 'spare', 'dark', 'fault'])->default('spare');
            $table->foreignId('source_node_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->foreignId('target_node_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->string('color_name', 50)->nullable()->comment('Denormalized for quick display');
            $table->json('metadata')->nullable()->comment('OTDR loss, splice points, etc.');
            $table->timestamps();

            $table->unique(['cable_id', 'core_number']);
            $table->index(['cable_id', 'tube_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_cores');
    }
};
