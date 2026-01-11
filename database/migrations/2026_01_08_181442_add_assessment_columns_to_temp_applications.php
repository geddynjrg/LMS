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
            $table->string('risk_level')->nullable()->after('status'); // low, medium, high
            $table->json('assessment_data')->nullable()->after('risk_level'); // flags and stats
            $table->integer('amount_approved')->nullable()->default(0)->after('loan_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->dropColumn(['risk_level', 'assessment_data', 'amount_approved']);
        });
    }
};
