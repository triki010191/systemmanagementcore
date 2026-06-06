<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS: pengaturan aplikasi yang dapat dikustomisasi pemilik tanpa ubah kode.
 *
 * @see docs/migrations/2026_06_05_143010_create_system_settings_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->comment('branding, localization, features, integration');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'text', 'boolean', 'integer', 'json', 'image'])->default('string');
            $table->string('label')->nullable()->comment('Human label for CMS UI');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false)->comment('Exposed to frontend views');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
