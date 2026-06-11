<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE core_joints MODIFY joint_type ENUM('otb_odc', 'odc_odp', 'odc_odc') NOT NULL DEFAULT 'otb_odc'");
    }

    public function down(): void
    {
        DB::table('core_joints')->where('joint_type', 'odc_odc')->delete();

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE core_joints MODIFY joint_type ENUM('otb_odc', 'odc_odp') NOT NULL DEFAULT 'otb_odc'");
    }
};
