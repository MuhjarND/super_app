<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSuratMasukReadsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('surat_masuk_reads')) {
            Schema::create('surat_masuk_reads', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('surat_masuk_id');
                $table->unsignedBigInteger('user_id');
                $table->dateTime('read_at')->nullable();
                $table->timestamps();

                $table->unique(['surat_masuk_id', 'user_id'], 'smr_surat_user_unique');
                $table->index(['user_id', 'read_at', 'surat_masuk_id'], 'smr_user_read_surat_idx');
                $table->foreign('surat_masuk_id')->references('id')->on('surat_masuks')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        $now = now();
        $userIds = DB::table('users')->pluck('id')->all();

        if (empty($userIds)) {
            return;
        }

        DB::table('surat_masuks')
            ->orderBy('id')
            ->pluck('id')
            ->chunk(100)
            ->each(function ($suratIds) use ($userIds, $now) {
                $rows = [];

                foreach ($suratIds as $suratId) {
                    foreach ($userIds as $userId) {
                        $rows[] = [
                            'surat_masuk_id' => $suratId,
                            'user_id' => $userId,
                            'read_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (!empty($rows)) {
                    DB::table('surat_masuk_reads')->insertOrIgnore($rows);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('surat_masuk_reads');
    }
}
