<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified network asset nodes — fondasi Network Graph & Fiber Path Tracing.
 * Semua aset (POP → Customer) dengan GPS wajib dan QR tracking.
 *
 * @see docs/migrations/2026_06_05_143002_create_network_nodes_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('type', ['pop', 'olt', 'otb', 'odc', 'splitter', 'odp', 'customer']);
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->foreignId('parent_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->json('metadata')->nullable()->comment('Type-specific: ratio, port_count, customer_id, etc.');
            $table->enum('status', ['active', 'inactive', 'maintenance', 'fault'])->default('active');
            $table->string('qr_code_path')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
            $table->index(['latitude', 'longitude']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_nodes');
    }
};
