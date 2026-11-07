<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait BuildsVotingSchema
{
    protected function createVotingSchema()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->nullable();
            $table->string('password')->nullable(); $table->boolean('status_aktif_pegawai')->default(true);
            $table->unsignedInteger('hirarki')->nullable(); $table->unsignedBigInteger('jabatan_id')->nullable();
            $table->string('jabatan_keterangan')->nullable(); $table->string('no_hp')->nullable();
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('jabatans', function (Blueprint $table) { $table->id(); $table->string('nama'); $table->string('kode'); $table->timestamps(); });
        Schema::create('roles', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('display_name'); $table->timestamps(); });
        Schema::create('role_user', function (Blueprint $table) { $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('user_id'); });
        Schema::create('votings', function (Blueprint $table) {
            $table->id(); $table->string('judul'); $table->text('deskripsi')->nullable();
            $table->string('status')->default('draft'); $table->boolean('select_all_participants')->default(false);
            $table->string('public_code')->unique(); $table->string('token_qr')->nullable();
            $table->unsignedBigInteger('created_by'); $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('participant_notified_at')->nullable(); $table->timestamps();
        });
        Schema::create('voting_items', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('voting_id'); $table->string('judul');
            $table->text('deskripsi')->nullable(); $table->boolean('uses_candidates')->default(true);
            $table->unsignedInteger('urutan')->default(1); $table->timestamps();
            $table->foreign('voting_id')->references('id')->on('votings')->onDelete('cascade');
        });
        Schema::create('voting_candidates', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('voting_item_id'); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nama_snapshot'); $table->string('jabatan_snapshot')->nullable();
            $table->string('image_path')->nullable(); $table->string('image_name')->nullable();
            $table->string('image_mime')->nullable(); $table->unsignedInteger('image_size')->nullable();
            $table->unsignedInteger('urutan')->default(1); $table->timestamps();
            $table->unique(['voting_item_id', 'user_id']);
            $table->foreign('voting_item_id')->references('id')->on('voting_items')->onDelete('cascade');
        });
        Schema::create('voting_participants', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('voting_id'); $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(1); $table->timestamp('voted_at')->nullable(); $table->timestamps();
            $table->unique(['voting_id', 'user_id']);
        });
        Schema::create('voting_votes', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('voting_id'); $table->unsignedBigInteger('voting_item_id');
            $table->unsignedBigInteger('voting_candidate_id'); $table->unsignedBigInteger('user_id');
            $table->timestamp('voted_at'); $table->timestamps(); $table->unique(['voting_item_id', 'user_id']);
        });
    }
}
