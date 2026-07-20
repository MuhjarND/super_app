<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateModuleSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('module_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module_key', 80)->unique();
            $table->string('status', 20)->default('published');
            $table->string('custom_label', 80)->nullable();
            $table->text('maintenance_message')->nullable();
            $table->boolean('show_desktop')->default(true);
            $table->boolean('show_mobile')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->json('settings')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        $rows = [];
        foreach (array_keys(config('modules.catalog', [])) as $order => $key) {
            $rows[] = [
                'module_key' => $key,
                'status' => 'published',
                'show_desktop' => true,
                'show_mobile' => true,
                'display_order' => ($order + 1) * 10,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('module_settings')->insert($rows);
        }
    }

    public function down()
    {
        Schema::dropIfExists('module_settings');
    }
}
