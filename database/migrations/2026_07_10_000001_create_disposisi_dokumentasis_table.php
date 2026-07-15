<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisposisiDokumentasisTable extends Migration
{
    public function up()
    {
        Schema::create('disposisi_dokumentasis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('disposisi_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();

            $table->foreign('disposisi_id')
                ->references('id')
                ->on('disposisis')
                ->onDelete('cascade');
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            $table->index(['disposisi_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('disposisi_dokumentasis');
    }
}
