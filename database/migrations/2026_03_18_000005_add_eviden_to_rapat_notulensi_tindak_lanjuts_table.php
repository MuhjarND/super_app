<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidenToRapatNotulensiTindakLanjutsTable extends Migration
{
    public function up()
    {
        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_path')) {
                $table->string('eviden_path')->nullable()->after('catatan_penyelesaian');
            }
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_name')) {
                $table->string('eviden_name')->nullable()->after('eviden_path');
            }
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_mime')) {
                $table->string('eviden_mime')->nullable()->after('eviden_name');
            }
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_size')) {
                $table->unsignedBigInteger('eviden_size')->nullable()->after('eviden_mime');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_size')) {
                $table->dropColumn('eviden_size');
            }
            if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_mime')) {
                $table->dropColumn('eviden_mime');
            }
            if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_name')) {
                $table->dropColumn('eviden_name');
            }
            if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'eviden_path')) {
                $table->dropColumn('eviden_path');
            }
        });
    }
}
