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
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->enum('lms_to_loan_app_sync_status', ['pending', 'processing', 'completed', 'failed'])
                ->after('sync_status')
                ->comment('Values: pending, processing, completed, failed');

            $table->timestamp('lms_to_loan_app_synced_at')->nullable()
                ->after('lms_to_loan_app_sync_status');

            $table->text('lms_to_loan_app_sync_error')->nullable()
                ->after('lms_to_loan_app_synced_at');

            $table->index('lms_to_loan_app_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->dropIndex(['lms_to_loan_app_sync_status']);
            $table->dropColumn(['lms_to_loan_app_sync_status', 'lms_to_loan_app_synced_at', 'lms_to_loan_app_sync_error']);
        });
    }
};
