<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §4.2 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cable_tubes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_id')->constrained('fiber_cables')->cascadeOnDelete();
            $table->unsignedTinyInteger('tube_number');
            $table->foreignId('tube_color_id')->constrained('tube_colors')->restrictOnDelete();
            $table->unsignedTinyInteger('core_count')->default(12);
            $table->timestamps();

            $table->unique(['cable_id', 'tube_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cable_tubes');
    }
};
