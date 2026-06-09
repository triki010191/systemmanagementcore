<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_joints', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('network_node_id')->constrained('network_nodes')->cascadeOnDelete();
            $table->foreignId('fiber_core_a_id')->constrained('fiber_cores')->restrictOnDelete();
            $table->foreignId('fiber_core_b_id')->constrained('fiber_cores')->restrictOnDelete();
            $table->decimal('splice_loss_db', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('spliced_at')->nullable();
            $table->enum('status', ['active', 'removed'])->default('active');
            $table->timestamps();

            $table->index('network_node_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_joints');
    }
};
