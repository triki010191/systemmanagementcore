<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi warna tube standar EIA/TIA-598-A (12 tube per cable).
 * Digunakan oleh fiber_cores dan network_links untuk Tube Color Management.
 *
 * @see docs/migrations/2026_06_05_143001_create_tube_colors_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tube_colors', function (Blueprint $table) {
            $table->id();
            $table->string('standard', 20)->default('eia_tia_598a');
            $table->unsignedTinyInteger('tube_number');
            $table->string('color_name', 50);
            $table->char('hex_code', 7);
            $table->string('tracer_pattern', 50)->nullable()->comment('solid, stripe, etc. for 24/48 core');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['standard', 'tube_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tube_colors');
    }
};
