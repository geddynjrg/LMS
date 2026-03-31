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
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->foreignId('group_loan_id')
                ->nullable()
                ->after('group_id')
                ->constrained('group_loans')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->dropForeign(['group_loan_id']);
            $table->dropColumn('group_loan_id');
        });
    }
};
