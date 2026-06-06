<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §7.1 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_node_id')->constrained('network_nodes')->cascadeOnDelete();
            $table->enum('photo_type', ['location', 'device', 'label', 'splice', 'other']);
            $table->string('file_path');
            $table->string('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['network_node_id', 'photo_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_photos');
    }
};
