<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->text('hq_accountant_resubmit_notes')->nullable()->after('accountant_reviewed_by');
            $table->timestamp('hq_accountant_resubmitted_at')->nullable()->after('hq_accountant_resubmit_notes');
            $table->unsignedBigInteger('hq_accountant_resubmitted_by')->nullable()->after('hq_accountant_resubmitted_at');

            $table->foreign('hq_accountant_resubmitted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->dropForeign(['hq_accountant_resubmitted_by']);
            $table->dropColumn([
                'hq_accountant_resubmit_notes',
                'hq_accountant_resubmitted_at',
                'hq_accountant_resubmitted_by',
            ]);
        });
    }
};
