<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryModuleTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('inventory_units')) {
            Schema::create('inventory_units', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_conditions')) {
            Schema::create('inventory_conditions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_rooms')) {
            Schema::create('inventory_rooms', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_brands')) {
            Schema::create('inventory_brands', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_authorities')) {
            Schema::create('inventory_authorities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('nip')->nullable();
                $table->string('position')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('inventory_item_details')) {
            Schema::create('inventory_item_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('inventory_item_id');
                $table->string('sub_code')->unique();
                $table->string('nup')->nullable()->index();
                $table->string('name');
                $table->date('acquisition_date')->nullable();
                $table->decimal('acquisition_value', 15, 2)->default(0);
                $table->unsignedBigInteger('inventory_unit_id')->nullable();
                $table->unsignedBigInteger('inventory_condition_id')->nullable();
                $table->unsignedBigInteger('inventory_room_id')->nullable();
                $table->unsignedBigInteger('inventory_brand_id')->nullable();
                $table->text('notes')->nullable();
                $table->string('photo_path')->nullable();
                $table->string('photo_original_name')->nullable();
                $table->string('qr_token', 80)->nullable()->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
                $table->foreign('inventory_unit_id')->references('id')->on('inventory_units')->onDelete('set null');
                $table->foreign('inventory_condition_id')->references('id')->on('inventory_conditions')->onDelete('set null');
                $table->foreign('inventory_room_id')->references('id')->on('inventory_rooms')->onDelete('set null');
                $table->foreign('inventory_brand_id')->references('id')->on('inventory_brands')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('inventory_maintenance_transactions')) {
            Schema::create('inventory_maintenance_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('inventory_item_id');
                $table->unsignedBigInteger('inventory_item_detail_id')->nullable();
                $table->date('transaction_date');
                $table->text('description');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('source_type')->default('manual');
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
                $table->foreign('inventory_item_detail_id')->references('id')->on('inventory_item_details')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['transaction_date', 'inventory_item_id']);
            });
        }

        if (!Schema::hasTable('inventory_transaction_attachments')) {
            Schema::create('inventory_transaction_attachments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('inventory_maintenance_transaction_id');
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();

                $table->foreign('inventory_maintenance_transaction_id', 'inventory_transaction_attachments_tx_fk')
                    ->references('id')->on('inventory_maintenance_transactions')->onDelete('cascade');
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transaction_attachments');
        Schema::dropIfExists('inventory_maintenance_transactions');
        Schema::dropIfExists('inventory_item_details');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_authorities');
        Schema::dropIfExists('inventory_brands');
        Schema::dropIfExists('inventory_rooms');
        Schema::dropIfExists('inventory_conditions');
        Schema::dropIfExists('inventory_units');
    }
}
