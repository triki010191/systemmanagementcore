<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS: menu navigasi yang dapat dikustomisasi (sidebar, header, footer).
 *
 * @see docs/migrations/2026_06_05_143011_create_navigation_menus_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->enum('location', ['sidebar', 'header', 'footer', 'mobile'])->default('sidebar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menus');
    }
};
