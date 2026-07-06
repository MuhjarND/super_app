<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDelegationLookupIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('disposisis', ['kepada_user_id', 'status', 'surat_masuk_id'], 'disp_to_user_status_surat_idx');
        $this->addIndexIfMissing('disposisis', ['kepada_jabatan_id', 'status', 'surat_masuk_id'], 'disp_to_jabatan_status_surat_idx');
        $this->addIndexIfMissing('disposisis', ['dari_user_id', 'surat_masuk_id'], 'disp_from_user_surat_idx');
        $this->addIndexIfMissing('disposisis', ['status', 'created_at'], 'disp_status_created_idx');

        $this->addIndexIfMissing('user_jabatan_delegations', ['user_id', 'is_active'], 'ujd_user_active_idx');
        $this->addIndexIfMissing('user_jabatan_delegations', ['jabatan_id', 'is_active'], 'ujd_jabatan_active_idx');

        $this->addIndexIfMissing('rapat_approvals', ['approver_id', 'status'], 'rapat_approval_approver_status_idx');
        $this->addIndexIfMissing('rapat_notulensi_approvals', ['approver_id', 'status'], 'notulensi_approval_approver_status_idx');
        $this->addIndexIfMissing('leave_approvals', ['approver_id', 'status'], 'leave_approval_approver_status_idx');
        $this->addIndexIfMissing('surat_keluar_approvals', ['approver_id', 'status'], 'surat_keluar_approval_approver_status_idx');
        $this->addIndexIfMissing('zi_activity_approvals', ['approver_id', 'status'], 'zi_approval_approver_status_idx');
    }

    public function down()
    {
        $this->dropIndexIfExists('disposisis', 'disp_to_user_status_surat_idx');
        $this->dropIndexIfExists('disposisis', 'disp_to_jabatan_status_surat_idx');
        $this->dropIndexIfExists('disposisis', 'disp_from_user_surat_idx');
        $this->dropIndexIfExists('disposisis', 'disp_status_created_idx');

        $this->dropIndexIfExists('user_jabatan_delegations', 'ujd_user_active_idx');
        $this->dropIndexIfExists('user_jabatan_delegations', 'ujd_jabatan_active_idx');

        $this->dropIndexIfExists('rapat_approvals', 'rapat_approval_approver_status_idx');
        $this->dropIndexIfExists('rapat_notulensi_approvals', 'notulensi_approval_approver_status_idx');
        $this->dropIndexIfExists('leave_approvals', 'leave_approval_approver_status_idx');
        $this->dropIndexIfExists('surat_keluar_approvals', 'surat_keluar_approval_approver_status_idx');
        $this->dropIndexIfExists('zi_activity_approvals', 'zi_approval_approver_status_idx');
    }

    protected function addIndexIfMissing($table, array $columns, $indexName)
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    protected function dropIndexIfExists($table, $indexName)
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    protected function indexExists($table, $indexName)
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
}
