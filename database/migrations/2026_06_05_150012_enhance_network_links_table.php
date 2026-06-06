<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §3.2 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_links', function (Blueprint $table) {
            $table->enum('direction', ['upstream', 'downstream', 'bidirectional'])
                ->default('bidirectional')->after('link_type');
            $table->decimal('loss_db', 5, 2)->nullable()->after('length_meters');
        });
    }

    public function down(): void
    {
        Schema::table('network_links', function (Blueprint $table) {
            $table->dropColumn(['direction', 'loss_db']);
        });
    }
};
