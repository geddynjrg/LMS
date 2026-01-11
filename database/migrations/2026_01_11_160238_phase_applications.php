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
        Schema::create('phase_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_phase_id')->constrained('loan_phases')->onDelete('cascade');
            $table->foreignId('temp_application_id')->constrained('temp_applications')->onDelete('cascade');

            // Financials
            $table->decimal('amount_applied', 12, 2);
            $table->decimal('amount_approved', 12, 2)->nullable();
            $table->decimal('amount_issued', 12, 2)->nullable();

            // Status & Workflow (With Index)
            $table->enum('status', [
                'pending', 'pending_submission_to_hq', 'pending_hq_approval', 'approved',
                'rejected', 'disbursed', 'in_training',
                'adjusted', 'deferred'
            ])->default('pending')->index();

            // Logistics
            $table->text('cheque_number')->nullable();
            $table->date('disbusement_date')->nullable();
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->enum('recommendation', [
                'strongly_recommend', 'recommend', 'neutral', 'not_recommend'
            ])->nullable();

            $table->timestamps();

            // Your Custom Indexes & Unique Constraints
            $table->unique(['loan_phase_id', 'temp_application_id'], 'phase_app_unique');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_applications');
    }
};
