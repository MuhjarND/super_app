<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RemoveBidangsAndConsolidateUnits extends Migration
{
    public function up()
    {
        $now = now();
        $units = [
            'PIMPINAN' => [
                'nama' => 'Pimpinan',
                'keterangan' => 'Ketua dan Wakil Ketua PTA',
            ],
            'HAKIM_TINGGI' => [
                'nama' => 'Hakim Tinggi',
                'keterangan' => 'Hakim Tinggi PTA Papua Barat',
            ],
            'KESEKRETARIATAN' => [
                'nama' => 'Kesekretariatan',
                'keterangan' => 'Sekretaris dan unsur pendukung kesekretariatan',
            ],
            'KEPANITERAAN' => [
                'nama' => 'Kepaniteraan',
                'keterangan' => 'Panitera dan unsur kepaniteraan',
            ],
        ];

        foreach ($units as $kode => $attributes) {
            DB::table('units')->updateOrInsert(
                ['kode' => $kode],
                array_merge($attributes, ['updated_at' => $now, 'created_at' => $now])
            );
        }

        $unitIds = DB::table('units')
            ->whereIn('kode', array_keys($units))
            ->pluck('id', 'kode');

        $obsoleteUnitIds = DB::table('units')
            ->whereNotIn('kode', array_keys($units))
            ->pluck('id');

        if ($obsoleteUnitIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('unit_id', $obsoleteUnitIds)
                ->update(['unit_id' => $unitIds['KESEKRETARIATAN']]);

            DB::table('jabatans')
                ->whereIn('unit_id', $obsoleteUnitIds)
                ->update(['unit_id' => $unitIds['KESEKRETARIATAN']]);
        }

        DB::table('users')
            ->whereNull('unit_id')
            ->update(['unit_id' => $unitIds['KESEKRETARIATAN']]);

        DB::table('users')
            ->whereRaw('LOWER(COALESCE(jabatan_keterangan, ?)) LIKE ?', ['', '%hakim tinggi%'])
            ->update(['unit_id' => $unitIds['HAKIM_TINGGI']]);

        DB::table('jabatans')
            ->whereIn('kode', ['KPTA', 'WKPTA'])
            ->update(['unit_id' => $unitIds['PIMPINAN']]);

        DB::table('jabatans')
            ->whereIn('kode', [
                'SEK', 'ADMIN_SURAT', 'OPR_SM', 'KABAG_KEPEG', 'KABAG_UMUM',
                'KASUBAG_KEPEG', 'KASUBAG_RENPRO', 'KASUBAG_LAPKEU', 'KASUBAG_TURT',
            ])
            ->update(['unit_id' => $unitIds['KESEKRETARIATAN']]);

        DB::table('jabatans')
            ->whereIn('kode', ['PAN', 'PANMUD_BANDING', 'PANMUD_HUKUM'])
            ->update(['unit_id' => $unitIds['KEPANITERAAN']]);

        DB::table('units')->whereNotIn('kode', array_keys($units))->delete();

        if (Schema::hasColumn('users', 'bidang_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['bidang_id']);
                $table->dropColumn('bidang_id');
            });
        }

        Schema::dropIfExists('bidangs');
    }

    public function down()
    {
        if (!Schema::hasTable('bidangs')) {
            Schema::create('bidangs', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode')->unique();
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'bidang_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('bidang_id')->nullable()->after('unit_id');
                $table->foreign('bidang_id')->references('id')->on('bidangs')->onDelete('set null');
            });
        }
    }
}
