<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.5 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->foreignId('odc_id')->nullable()->constrained('odcs')->nullOnDelete();
            $table->unsignedSmallInteger('port_capacity')->default(0);
            $table->unsignedSmallInteger('port_used')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odps');
    }
};
