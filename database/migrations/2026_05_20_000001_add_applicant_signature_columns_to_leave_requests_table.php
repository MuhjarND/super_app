<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApplicantSignatureColumnsToLeaveRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'applicant_signature_path')) {
                $table->string('applicant_signature_path')->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('leave_requests', 'applicant_signature_mime')) {
                $table->string('applicant_signature_mime', 100)->nullable()->after('applicant_signature_path');
            }

            if (!Schema::hasColumn('leave_requests', 'applicant_signature_size')) {
                $table->unsignedBigInteger('applicant_signature_size')->nullable()->after('applicant_signature_mime');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            foreach (['applicant_signature_size', 'applicant_signature_mime', 'applicant_signature_path'] as $column) {
                if (Schema::hasColumn('leave_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
