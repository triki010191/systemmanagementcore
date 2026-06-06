<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS: modul fitur on/off + konfigurasi per modul oleh pemilik aplikasi.
 *
 * @see docs/migrations/2026_06_05_143013_create_cms_modules_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_modules', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_core')->default(false)->comment('Cannot be disabled');
            $table->json('config')->nullable()->comment('Module-specific settings');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_modules');
    }
};
