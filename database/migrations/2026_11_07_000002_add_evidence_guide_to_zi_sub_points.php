<?php

use App\Services\ProgressZi\ZiEvidenceGuideService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEvidenceGuideToZiSubPoints extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('zi_guideline_sub_points', 'evidence_guide_key')) {
            Schema::table('zi_guideline_sub_points', function (Blueprint $table) {
                $table->string('evidence_guide_key', 50)->nullable()->unique();
            });
        }
        app(ZiEvidenceGuideService::class)->sync();
    }

    public function down()
    {
        // Keep reference rows: collected evidence may already be attached to them.
        Schema::table('zi_guideline_sub_points', function (Blueprint $table) {
            $table->dropUnique(['evidence_guide_key']);
            $table->dropColumn('evidence_guide_key');
        });
    }
}
