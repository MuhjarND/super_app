<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSatkerTargetsToRapatInvitations extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rapat_satker')) {
            Schema::create('rapat_satker', function (Blueprint $table) {
                $table->unsignedBigInteger('rapat_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->primary(['rapat_id', 'user_id']);
                $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('rapat_peserta') && Schema::hasTable('roles') && Schema::hasTable('role_user')) {
            $now = now('Asia/Jayapura');
            $existingTargets = DB::table('rapat_peserta')
                ->join('rapats', 'rapats.id', '=', 'rapat_peserta.rapat_id')
                ->join('role_user', 'role_user.user_id', '=', 'rapat_peserta.user_id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('rapats.bersama_satker', true)
                ->where('roles.name', 'satker')
                ->select('rapat_peserta.rapat_id', 'rapat_peserta.user_id')
                ->distinct()
                ->get();

            foreach ($existingTargets as $target) {
                DB::table('rapat_satker')->updateOrInsert(
                    ['rapat_id' => $target->rapat_id, 'user_id' => $target->user_id],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->index('rapat_id', 'sk_rapat_lookup_idx');
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropUnique('surat_keluars_rapat_id_unique');
            $table->unsignedBigInteger('satker_id')->nullable()->after('rapat_id');
            $table->boolean('is_satker_collective')->default(false)->after('satker_id');
            $table->foreign('satker_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['rapat_id', 'satker_id'], 'sk_rapat_satker_idx');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('surat_keluars') && Schema::hasColumn('surat_keluars', 'satker_id')) {
            Schema::table('surat_keluars', function (Blueprint $table) {
                $table->dropForeign(['satker_id']);
                $table->dropIndex('sk_rapat_satker_idx');
                $table->dropColumn(['satker_id', 'is_satker_collective']);
            });
        }

        Schema::dropIfExists('rapat_satker');
    }
}
