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
            $table->renameColumn('training_date', 'training_start_date');
        });

        Schema::table('loan_phases', function (Blueprint $table) {
            $table->date('training_end_date')->nullable()->after('training_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->renameColumn('training_start_date', 'training_date');
            $table->dropColumn(['training_end_date']);
        });
    }
};
