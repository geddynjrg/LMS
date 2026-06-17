<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('phase_applications')) {
            return;
        }

        if (Schema::hasColumn('phase_applications', 'disbusement_date')
            && !Schema::hasColumn('phase_applications', 'disbursement_date')) {
            Schema::table('phase_applications', function (Blueprint $table) {
                $table->renameColumn('disbusement_date', 'disbursement_date');
            });

            return;
        }

        if (!Schema::hasColumn('phase_applications', 'disbursement_date')
            && !Schema::hasColumn('phase_applications', 'disbusement_date')) {
            Schema::table('phase_applications', function (Blueprint $table) {
                $table->dateTime('disbursement_date')->nullable()->after('cheque_number');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('phase_applications')) {
            return;
        }

        if (Schema::hasColumn('phase_applications', 'disbursement_date')
            && !Schema::hasColumn('phase_applications', 'disbusement_date')) {
            Schema::table('phase_applications', function (Blueprint $table) {
                $table->renameColumn('disbursement_date', 'disbusement_date');
            });
        }
    }
};
