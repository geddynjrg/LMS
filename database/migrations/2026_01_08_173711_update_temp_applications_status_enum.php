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
            $table->enum('status', [
                'draft',
                'submitted',
                'under_constituency_review',
                'under_hq_review',
                'approved',
                'rejected',
                'disbursed',
                'closed'
            ])->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            // Revert to original statuses
            $table->enum('status', [
                'draft',
                'submitted',
                'under_review',
                'approved',
                'rejected',
                'disbursed',
                'closed'
            ])->default('draft')->change();
        });
    }
};
