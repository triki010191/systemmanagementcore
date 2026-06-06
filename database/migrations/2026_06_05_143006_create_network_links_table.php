<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hubungan antar node (Network Graph edges) — inti Fiber Path Tracing.
 *
 * @see docs/migrations/2026_06_05_143006_create_network_links_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_node_id')->constrained('network_nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('network_nodes')->cascadeOnDelete();
            $table->enum('link_type', ['fiber_cable', 'patch_cord', 'splitter_connection', 'splice', 'drop']);
            $table->foreignId('cable_id')->nullable()->constrained('fiber_cables')->nullOnDelete();
            $table->foreignId('fiber_core_id')->nullable()->constrained('fiber_cores')->nullOnDelete();
            $table->foreignId('source_port_id')->nullable()->constrained('splitter_ports')->nullOnDelete();
            $table->foreignId('target_port_id')->nullable()->constrained('splitter_ports')->nullOnDelete();
            $table->unsignedTinyInteger('core_number')->nullable();
            $table->unsignedTinyInteger('tube_number')->nullable();
            $table->foreignId('tube_color_id')->nullable()->constrained('tube_colors')->nullOnDelete();
            $table->decimal('length_meters', 10, 2)->nullable();
            $table->json('metadata')->nullable()->comment('loss_dB, OTDR, technician notes');
            $table->enum('status', ['active', 'cut', 'spare', 'planned'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_node_id', 'target_node_id']);
            $table->index('link_type');
            $table->index('fiber_core_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_links');
    }
};
