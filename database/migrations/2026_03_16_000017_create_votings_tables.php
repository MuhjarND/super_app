<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVotingsTables extends Migration
{
    public function up()
    {
        Schema::create('votings', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('select_all_participants')->default(false);
            $table->string('public_code')->unique();
            $table->string('token_qr')->nullable()->unique();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('voting_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voting_id');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();

            $table->foreign('voting_id')->references('id')->on('votings')->onDelete('cascade');
        });

        Schema::create('voting_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voting_item_id');
            $table->unsignedBigInteger('user_id');
            $table->string('nama_snapshot');
            $table->string('jabatan_snapshot')->nullable();
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['voting_item_id', 'user_id']);
            $table->foreign('voting_item_id')->references('id')->on('voting_items')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('voting_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voting_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(1);
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();

            $table->unique(['voting_id', 'user_id']);
            $table->foreign('voting_id')->references('id')->on('votings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('voting_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('voting_id');
            $table->unsignedBigInteger('voting_item_id');
            $table->unsignedBigInteger('voting_candidate_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('voted_at');
            $table->timestamps();

            $table->unique(['voting_item_id', 'user_id']);
            $table->foreign('voting_id')->references('id')->on('votings')->onDelete('cascade');
            $table->foreign('voting_item_id')->references('id')->on('voting_items')->onDelete('cascade');
            $table->foreign('voting_candidate_id')->references('id')->on('voting_candidates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voting_votes');
        Schema::dropIfExists('voting_participants');
        Schema::dropIfExists('voting_candidates');
        Schema::dropIfExists('voting_items');
        Schema::dropIfExists('votings');
    }
}
