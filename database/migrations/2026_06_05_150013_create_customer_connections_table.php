<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §6.2 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('odp_id')->constrained('odps')->restrictOnDelete();
            $table->foreignId('cable_core_id')->nullable()->constrained('fiber_cores')->nullOnDelete();
            $table->foreignId('splitter_port_id')->nullable()->constrained('splitter_ports')->nullOnDelete();
            $table->unsignedTinyInteger('odp_port_number')->nullable();
            $table->decimal('drop_length_m', 8, 2)->nullable();
            $table->date('connected_at')->nullable();
            $table->timestamps();

            $table->index('odp_id');
            $table->index('cable_core_id');
            $table->index('splitter_port_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_connections');
    }
};
