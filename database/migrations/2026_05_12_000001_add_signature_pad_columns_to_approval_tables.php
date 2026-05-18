<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignaturePadColumnsToApprovalTables extends Migration
{
    public function up()
    {
        foreach (['rapat_approvals', 'rapat_notulensi_approvals', 'leave_approvals', 'surat_keluar_approvals'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'signature_path')) {
                    $table->string('signature_path')->nullable()->after('acted_at');
                }

                if (!Schema::hasColumn($tableName, 'signature_mime')) {
                    $table->string('signature_mime', 100)->nullable()->after('signature_path');
                }

                if (!Schema::hasColumn($tableName, 'signature_size')) {
                    $table->unsignedBigInteger('signature_size')->nullable()->after('signature_mime');
                }
            });
        }
    }

    public function down()
    {
        foreach (['surat_keluar_approvals', 'leave_approvals', 'rapat_notulensi_approvals', 'rapat_approvals'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                foreach (['signature_size', 'signature_mime', 'signature_path'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
