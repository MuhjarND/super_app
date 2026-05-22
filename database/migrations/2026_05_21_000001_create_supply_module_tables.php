<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSupplyModuleTables extends Migration
{
    public function up()
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'operator_persediaan'],
            ['display_name' => 'Operator Persediaan', 'updated_at' => now(), 'created_at' => now()]
        );

        if (!Schema::hasTable('supply_items')) {
            Schema::create('supply_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 80)->nullable()->unique();
                $table->string('name');
                $table->string('unit', 50)->default('Pcs');
                $table->unsignedInteger('stock')->default(0);
                $table->unsignedInteger('minimum_stock')->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'name']);
            });
        }

        if (!Schema::hasTable('supply_requests')) {
            Schema::create('supply_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('request_number', 80)->unique();
                $table->unsignedBigInteger('user_id');
                $table->string('status', 40)->default('pending');
                $table->text('purpose');
                $table->text('operator_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('processed_by')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['status', 'submitted_at']);
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('supply_request_items')) {
            Schema::create('supply_request_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('supply_request_id');
                $table->unsignedBigInteger('supply_item_id')->nullable();
                $table->string('item_name_snapshot');
                $table->string('unit_snapshot', 50)->default('Pcs');
                $table->unsignedInteger('quantity_requested')->default(1);
                $table->unsignedInteger('quantity_fulfilled')->default(0);
                $table->timestamps();

                $table->foreign('supply_request_id')->references('id')->on('supply_requests')->onDelete('cascade');
                $table->foreign('supply_item_id')->references('id')->on('supply_items')->onDelete('set null');
                $table->index(['supply_item_id']);
            });
        }

        if (!Schema::hasTable('supply_pickups')) {
            Schema::create('supply_pickups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('supply_request_id')->nullable();
                $table->unsignedBigInteger('supply_request_item_id')->nullable();
                $table->unsignedBigInteger('supply_item_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->string('item_name_snapshot');
                $table->string('unit_snapshot', 50)->default('Pcs');
                $table->unsignedInteger('quantity')->default(1);
                $table->text('purpose')->nullable();
                $table->date('pickup_date');
                $table->string('receiver_signature_path')->nullable();
                $table->string('receiver_signature_mime')->nullable();
                $table->unsignedInteger('receiver_signature_size')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('supply_request_id')->references('id')->on('supply_requests')->onDelete('set null');
                $table->foreign('supply_request_item_id')->references('id')->on('supply_request_items')->onDelete('set null');
                $table->foreign('supply_item_id')->references('id')->on('supply_items')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['pickup_date', 'user_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('supply_pickups');
        Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests');
        Schema::dropIfExists('supply_items');
    }
}
