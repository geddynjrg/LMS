<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->string('accountant_review_status', 40)->nullable()->after('status');
            $table->text('accountant_notes')->nullable()->after('accountant_review_status');
            $table->timestamp('accountant_reviewed_at')->nullable()->after('accountant_notes');
            $table->unsignedBigInteger('accountant_reviewed_by')->nullable()->after('accountant_reviewed_at');

            $table->foreign('accountant_reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->dropForeign(['accountant_reviewed_by']);
            $table->dropColumn([
                'accountant_review_status',
                'accountant_notes',
                'accountant_reviewed_at',
                'accountant_reviewed_by',
            ]);
        });
    }
};
