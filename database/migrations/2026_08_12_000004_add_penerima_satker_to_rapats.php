<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenerimaSatkerToRapats extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rapats') || Schema::hasColumn('rapats', 'penerima_satker')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->string('penerima_satker', 150)->nullable()->after('tujuan_surat');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'penerima_satker')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn('penerima_satker');
        });
    }
}
