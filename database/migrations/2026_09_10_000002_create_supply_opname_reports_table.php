<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSupplyOpnameReportsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('supply_opname_reports')) {
            return;
        }

        Schema::create('supply_opname_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('report_period');
            $table->date('opname_date');
            $table->string('nomor_surat', 160)->nullable()->unique();
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->unsignedSmallInteger('tahun_surat')->nullable();
            $table->json('snapshot')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique('report_period');
            $table->index(['tahun_surat', 'nomor_urut']);
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('supply_opname_reports');
    }
}
