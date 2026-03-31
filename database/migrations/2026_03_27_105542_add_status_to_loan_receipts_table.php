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
        Schema::table('loan_receipts', function (Blueprint $column) {
            $column->enum('status', ['active', 'voided'])
                   ->default('active')
                   ->after('reference_number')
                   ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_receipts', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
