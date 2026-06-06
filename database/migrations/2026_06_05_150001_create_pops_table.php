<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.1 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->string('upstream_provider', 100)->nullable();
            $table->string('router_model', 100)->nullable();
            $table->boolean('monitoring_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pops');
    }
};
