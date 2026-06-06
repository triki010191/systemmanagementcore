<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS: item menu dengan permission gate & nested structure.
 *
 * @see docs/migrations/2026_06_05_143012_create_navigation_items_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('label');
            $table->string('icon', 50)->nullable()->comment('Heroicon/Material icon name');
            $table->string('route_name', 100)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('permission', 100)->nullable()->comment('Spatie permission required');
            $table->string('module_slug', 50)->nullable()->comment('Hide if cms_modules disabled');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
