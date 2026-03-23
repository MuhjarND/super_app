<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveProfileFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status_asn')) { $table->string('status_asn', 30)->default('PNS')->after('no_hp'); }
            if (!Schema::hasColumn('users', 'tmt_pns')) { $table->date('tmt_pns')->nullable()->after('status_asn'); }
            if (!Schema::hasColumn('users', 'atasan_langsung_id')) { $table->unsignedBigInteger('atasan_langsung_id')->nullable()->after('tmt_pns'); }
            if (!Schema::hasColumn('users', 'pejabat_berwenang_id')) { $table->unsignedBigInteger('pejabat_berwenang_id')->nullable()->after('atasan_langsung_id'); }
            if (!Schema::hasColumn('users', 'jumlah_anak')) { $table->unsignedInteger('jumlah_anak')->default(0)->after('pejabat_berwenang_id'); }
            if (!Schema::hasColumn('users', 'status_aktif_pegawai')) { $table->boolean('status_aktif_pegawai')->default(true)->after('jumlah_anak'); }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['status_asn', 'tmt_pns', 'atasan_langsung_id', 'pejabat_berwenang_id', 'jumlah_anak', 'status_aktif_pegawai'] as $column) {
                if (Schema::hasColumn('users', $column)) { $table->dropColumn($column); }
            }
        });
    }
}
