<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.6 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('splitters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->foreignId('odc_id')->nullable()->constrained('odcs')->nullOnDelete();
            $table->enum('ratio', ['1:2', '1:4', '1:8', '1:16', '1:32', '1:64'])->default('1:8');
            $table->unsignedTinyInteger('input_port_count')->default(1);
            $table->unsignedTinyInteger('output_port_count')->default(8);
            $table->string('brand', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('splitters');
    }
};
