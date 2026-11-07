<?php

use App\Services\ProgressZi\ZiEvidenceGuideService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZiEvidenceGuidesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('zi_evidence_guides')) {
            Schema::create('zi_evidence_guides', function (Blueprint $table) {
                $table->string('guide_key', 50)->primary();
                $table->string('source_name');
                $table->string('source_sha256', 64);
                $table->string('source_sheet', 100);
                $table->unsignedInteger('source_row');
                $table->string('group_type', 30);
                $table->string('area_code', 30);
                $table->string('area_title');
                $table->longText('area_guidance')->nullable();
                $table->string('point_key', 50);
                $table->string('point_code', 30);
                $table->string('point_title');
                $table->longText('point_guidance')->nullable();
                $table->string('display_code', 20);
                $table->text('title');
                $table->longText('explanation')->nullable();
                $table->longText('notes')->nullable();
                $table->string('answer_format', 50)->nullable();
                $table->longText('supporting_data');
                $table->longText('images');
                $table->timestamps();
            });
        }
        app(ZiEvidenceGuideService::class)->sync();
    }

    public function down()
    {
        Schema::dropIfExists('zi_evidence_guides');
    }
}
