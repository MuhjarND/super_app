<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAnswerModeToVotingItems extends Migration
{
    public function up()
    {
        // Papeda uses MySQL/MariaDB. Keep the existing candidate/vote keys so
        // historical ballots and results are preserved, without requiring DBAL.
        DB::statement('ALTER TABLE voting_candidates MODIFY user_id BIGINT UNSIGNED NULL');

        if (!Schema::hasColumn('voting_items', 'uses_candidates')) {
            Schema::table('voting_items', function (Blueprint $table) {
                $table->boolean('uses_candidates')->default(true);
            });
        }
    }

    public function down()
    {
        if (DB::table('voting_candidates')->whereNull('user_id')->exists()) {
            throw new \RuntimeException('Rollback dibatalkan: terdapat pilihan voting tanpa kandidat. Data voting harus dipertahankan.');
        }

        DB::statement('ALTER TABLE voting_candidates MODIFY user_id BIGINT UNSIGNED NOT NULL');
        Schema::table('voting_items', function (Blueprint $table) {
            $table->dropColumn('uses_candidates');
        });
    }
}
