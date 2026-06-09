<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_joints', function (Blueprint $table) {
            $table->enum('joint_type', ['otb_odc', 'odc_odp'])->default('otb_odc')->after('code');
            $table->foreignId('source_node_id')->nullable()->after('joint_type')->constrained('network_nodes')->nullOnDelete();
            $table->foreignId('target_node_id')->nullable()->after('source_node_id')->constrained('network_nodes')->nullOnDelete();
            $table->unsignedSmallInteger('core_number')->nullable()->after('target_node_id');
        });

        foreach (DB::table('core_joints')->get() as $joint) {
            $updates = ['joint_type' => 'otb_odc'];

            if ($joint->odp_id) {
                $updates['joint_type'] = 'odc_odp';
                $odp = DB::table('odps')->where('id', $joint->odp_id)->first();
                if ($odp) {
                    $updates['target_node_id'] = $odp->network_node_id;
                    if ($joint->odc_id) {
                        $odc = DB::table('odcs')->where('id', $joint->odc_id)->first();
                        $updates['source_node_id'] = $odc?->network_node_id;
                    }
                }
            } elseif ($joint->odc_id) {
                $odc = DB::table('odcs')->where('id', $joint->odc_id)->first();
                if ($odc) {
                    $updates['target_node_id'] = $odc->network_node_id;
                    $otbNodeId = DB::table('network_nodes')->where('id', $joint->network_node_id)->value('parent_id');
                    if ($otbNodeId) {
                        $updates['source_node_id'] = $otbNodeId;
                    }
                }
            }

            if ($joint->fiber_core_a_id) {
                $coreNumber = DB::table('fiber_cores')->where('id', $joint->fiber_core_a_id)->value('core_number');
                if ($coreNumber) {
                    $updates['core_number'] = $coreNumber;
                }
            }

            DB::table('core_joints')->where('id', $joint->id)->update($updates);
        }
    }

    public function down(): void
    {
        Schema::table('core_joints', function (Blueprint $table) {
            $table->dropForeign(['source_node_id']);
            $table->dropForeign(['target_node_id']);
            $table->dropColumn(['joint_type', 'source_node_id', 'target_node_id', 'core_number']);
        });
    }
};
