<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMagicLoginTokensTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_magic_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token_hash', 64)->unique();
            $table->text('destination_url');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_magic_login_tokens');
    }
}
