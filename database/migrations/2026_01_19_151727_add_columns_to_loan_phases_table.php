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
            $table->string('phase_ref')->after('id')->comment('Format: PHS/CON/YEAR/001');

            $table->text('hq_remarks')->nullable()->after('committee_remarks');
            $table->timestamp('hq_approved_at')->nullable()->after('submitted_to_hq_at');

            $table->date('training_date')->nullable()->after('review_venue');
            $table->string('training_venue')->nullable()->after('training_date');

            $table->timestamp('disbursed_at')->nullable()->after('hq_reviewed_at');

            // 2. Locking Mechanism
            $table->boolean('is_locked')->default(false)->after('status');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->unsignedBigInteger('locked_by')->nullable()->after('locked_at');

            // 3. Foreign key for locked_by (assuming it's a user)
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set NULL');

            $table->index('phase_ref');
            $table->index('training_date');
            $table->index('is_locked');

            $table->foreign('locked_by', 'fk_phase_locked_by_user')
                ->references('id')
                ->on('users')
                ->onDelete('set NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->dropForeign(['fk_phase_locked_by_user']);
            $table->dropIndex(['phase_ref', 'training_date', 'is_locked']);
            $table->dropColumn([
                'phase_ref', 'training_date', 'training_venue', 'hq_remarks',
                'hq_approved_at', 'disbursed_at',
                'is_locked', 'locked_at', 'locked_by'
            ]);
        });
    }
};
