<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.3 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otbs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->foreignId('olt_id')->nullable()->constrained('olts')->nullOnDelete();
            $table->unsignedTinyInteger('tray_count')->nullable();
            $table->unsignedSmallInteger('capacity_cores')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otbs');
    }
};
