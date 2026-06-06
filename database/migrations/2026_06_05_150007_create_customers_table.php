<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §5.8 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->unique()->constrained('network_nodes')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('service_type', ['home', 'business'])->default('home');
            $table->unsignedSmallInteger('vlan_tag')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('onu_serial', 100)->nullable();
            $table->decimal('rx_power_dbm', 5, 2)->nullable();
            $table->decimal('tx_power_dbm', 5, 2)->nullable();
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->date('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
