<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.4 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odcs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->foreignId('otb_id')->nullable()->constrained('otbs')->nullOnDelete();
            $table->unsignedSmallInteger('port_capacity')->default(0);
            $table->unsignedSmallInteger('port_used')->default(0);
            $table->string('split_ratio_default', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odcs');
    }
};
