<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeRapatSifatSuratDefaultToBiasa extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'sifat_surat')) {
            return;
        }

        DB::statement("ALTER TABLE `rapats` MODIFY `sifat_surat` VARCHAR(30) NOT NULL DEFAULT 'biasa'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'sifat_surat')) {
            return;
        }

        DB::statement("ALTER TABLE `rapats` MODIFY `sifat_surat` VARCHAR(30) NOT NULL DEFAULT 'penting'");
    }
}
