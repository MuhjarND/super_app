<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParafWorkflowToSuratKeluarApprovalsTable extends Migration
{
    public function up()
    {
        Schema::table('surat_keluar_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('paraf_user_id')->nullable()->after('approver_id');
            $table->string('paraf_status', 30)->default('not_required')->after('paraf_user_id');
            $table->text('paraf_note')->nullable()->after('paraf_status');
            $table->timestamp('paraf_at')->nullable()->after('paraf_note');

            $table->foreign('paraf_user_id', 'surat_keluar_approval_paraf_user_fk')
                ->references('id')->on('users')->onDelete('set null');
            $table->index(['paraf_user_id', 'paraf_status'], 'surat_keluar_approval_paraf_status_idx');
        });
    }

    public function down()
    {
        Schema::table('surat_keluar_approvals', function (Blueprint $table) {
            $table->dropForeign('surat_keluar_approval_paraf_user_fk');
            $table->dropIndex('surat_keluar_approval_paraf_status_idx');
            $table->dropColumn(['paraf_user_id', 'paraf_status', 'paraf_note', 'paraf_at']);
        });
    }
}
