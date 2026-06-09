<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odc_splitters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odc_id')->constrained('odcs')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->foreignId('input_cable_id')->nullable()->constrained('fiber_cables')->nullOnDelete();
            $table->unsignedSmallInteger('input_core_number');
            $table->enum('split_ratio', ['1:4', '1:8'])->default('1:4');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['odc_id', 'sort_order']);
        });

        Schema::create('odc_splitter_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odc_splitter_id')->constrained('odc_splitters')->cascadeOnDelete();
            $table->unsignedTinyInteger('output_port');
            $table->enum('target_type', ['odp', 'odc'])->nullable();
            $table->foreignId('target_node_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->foreignId('outbound_cable_id')->nullable()->constrained('fiber_cables')->nullOnDelete();
            $table->unsignedSmallInteger('outbound_core_number')->nullable();
            $table->foreignId('core_joint_id')->nullable()->constrained('core_joints')->nullOnDelete();
            $table->timestamps();

            $table->unique(['odc_splitter_id', 'output_port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odc_splitter_outputs');
        Schema::dropIfExists('odc_splitters');
    }
};
