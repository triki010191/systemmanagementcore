<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.2 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->foreignId('pop_id')->nullable()->constrained('pops')->nullOnDelete();
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedTinyInteger('slot_count')->nullable();
            $table->unsignedSmallInteger('pon_port_count')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('snmp_community', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
