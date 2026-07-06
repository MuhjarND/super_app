<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarkSuratKeluarWithFilesAsLengkap extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('surat_keluars')) {
            return;
        }

        $now = now('Asia/Jayapura');

        DB::table('surat_keluars')
            ->where('status', '!=', 'lengkap')
            ->where(function ($query) {
                $query->whereNotNull('file_path')
                    ->where('file_path', '!=', '');

                if (Schema::hasTable('surat_keluar_approvals')) {
                    $query->orWhereExists(function ($exists) {
                        $exists->select(DB::raw(1))
                            ->from('surat_keluar_approvals')
                            ->whereColumn('surat_keluar_approvals.surat_keluar_id', 'surat_keluars.id')
                            ->whereNotNull('surat_keluar_approvals.rendered_body')
                            ->where('surat_keluar_approvals.rendered_body', '!=', '');
                    });
                }

                if (Schema::hasColumn('surat_keluars', 'rapat_id')) {
                    $query->orWhereNotNull('rapat_id');
                }

                if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'letter_number')) {
                    $query->orWhereExists(function ($exists) {
                        $exists->select(DB::raw(1))
                            ->from('leave_requests')
                            ->whereColumn('leave_requests.letter_number', 'surat_keluars.nomor_surat');
                    });
                }

                if (Schema::hasTable('pdf_verifications')) {
                    $query->orWhereExists(function ($exists) {
                        $exists->select(DB::raw(1))
                            ->from('pdf_verifications')
                            ->where('pdf_verifications.module', 'surat_keluar')
                            ->whereColumn('pdf_verifications.document_id', 'surat_keluars.id')
                            ->whereNotNull('pdf_verifications.file_path')
                            ->where('pdf_verifications.file_path', '!=', '');
                    });
                }
            })
            ->update([
                'status' => 'lengkap',
                'updated_at' => $now,
            ]);
    }

    public function down()
    {
        //
    }
}
