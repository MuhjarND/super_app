<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageColumnsToVotingCandidatesTable extends Migration
{
    public function up()
    {
        Schema::table('voting_candidates', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('jabatan_snapshot');
            $table->string('image_name')->nullable()->after('image_path');
            $table->string('image_mime')->nullable()->after('image_name');
            $table->unsignedInteger('image_size')->nullable()->after('image_mime');
        });
    }

    public function down()
    {
        Schema::table('voting_candidates', function (Blueprint $table) {
            $table->dropColumn([
                'image_path',
                'image_name',
                'image_mime',
                'image_size',
            ]);
        });
    }
}
