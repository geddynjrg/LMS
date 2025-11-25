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
            $table->index(['constituency_code']);
            $table->index(['phase']);
        });

        Schema::table('group_loans', function (Blueprint $table) {
            $table->index(['loan_phase_id']);
            $table->index(['level']);
            $table->index(['disbursement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
