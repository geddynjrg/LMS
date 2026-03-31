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
        Schema::table('loan_receipts', function (Blueprint $table) {
            $table->text('void_reason')
                ->nullable()
                ->after('status');

            $table->foreignId('voided_by')
                ->nullable()
                ->after('void_reason')
                ->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_receipts', function (Blueprint $table) {
            $table->dropColumn(['void_reason', 'voided_by']);
        });
    }
};
