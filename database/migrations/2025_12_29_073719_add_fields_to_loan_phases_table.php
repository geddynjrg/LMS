<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            // New Detail Columns
            $table->string('phase_number')->nullable()->after('phase')->index();
            $table->string('phase_name')->nullable()->after('phase_number');
            $table->string('status')->default('draft')->after('phase_name')->index();

            // Review & Venue
            $table->date('review_date')->nullable()->after('status');
            $table->string('review_venue')->nullable()->after('review_date');
            $table->text('committee_remarks')->nullable()->after('review_venue');

            // Officers (Foreign Keys)
            $table->unsignedBigInteger('constituency_officer_id')->nullable()->after('committee_remarks');
            $table->unsignedBigInteger('hq_officer_id')->nullable()->after('constituency_officer_id');

            // Financials (Decimals for Precision)
            $table->decimal('total_amount_applied', 15, 2)->default(0)->after('hq_officer_id');
            $table->decimal('total_amount_approved', 15, 2)->default(0)->after('total_amount_applied');

            // Timestamps
            $table->timestamp('submitted_to_hq_at')->nullable()->after('total_amount_approved');
            $table->timestamp('hq_reviewed_at')->nullable()->after('submitted_to_hq_at');

            // Indexing & Foreign Key Constraints
            $table->foreign('constituency_officer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('hq_officer_id')->references('id')->on('users')->nullOnDelete();

            // Composite index if you often filter by constituency AND status
            $table->index(['constituency_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['constituency_officer_id']);
            $table->dropForeign(['hq_officer_id']);

            // Drop columns
            $table->dropColumn([
                'phase_number', 'phase_name', 'status', 'review_date',
                'review_venue', 'committee_remarks', 'constituency_officer_id',
                'hq_officer_id', 'total_amount_applied', 'total_amount_approved',
                'submitted_to_hq_at', 'hq_reviewed_at'
            ]);
        });
    }
};
