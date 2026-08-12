<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenutupUndanganToRapats extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rapats') || Schema::hasColumn('rapats', 'penutup_undangan')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->text('penutup_undangan')->nullable()->after('detail_tambahan');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'penutup_undangan')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn('penutup_undangan');
        });
    }
}
