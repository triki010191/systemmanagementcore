<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Port input/output per splitter — wajib untuk path tracing melalui splitter.
 *
 * @see docs/migrations/2026_06_05_143005_create_splitter_ports_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('splitter_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('splitter_node_id')->constrained('network_nodes')->cascadeOnDelete();
            $table->unsignedTinyInteger('port_number');
            $table->enum('direction', ['input', 'output']);
            $table->enum('status', ['active', 'spare', 'fault'])->default('spare');
            $table->string('label', 30)->nullable()->comment('e.g. IN-1, OUT-3');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['splitter_node_id', 'port_number', 'direction']);
            $table->index(['splitter_node_id', 'direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('splitter_ports');
    }
};
