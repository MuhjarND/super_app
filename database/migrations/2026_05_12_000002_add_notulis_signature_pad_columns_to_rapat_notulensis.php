<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotulisSignaturePadColumnsToRapatNotulensis extends Migration
{
    public function up()
    {
        Schema::table('rapat_notulensis', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat_notulensis', 'notulis_signature_path')) {
                $table->string('notulis_signature_path')->nullable()->after('file_size');
            }

            if (!Schema::hasColumn('rapat_notulensis', 'notulis_signature_mime')) {
                $table->string('notulis_signature_mime', 100)->nullable()->after('notulis_signature_path');
            }

            if (!Schema::hasColumn('rapat_notulensis', 'notulis_signature_size')) {
                $table->unsignedInteger('notulis_signature_size')->nullable()->after('notulis_signature_mime');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat_notulensis', function (Blueprint $table) {
            foreach (['notulis_signature_size', 'notulis_signature_mime', 'notulis_signature_path'] as $column) {
                if (Schema::hasColumn('rapat_notulensis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
