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
           $table->enum('status', [
                'draft',
                'in_review',
                'submitted_to_hq',
                'rejected',
                'approved',
                'in_training',
                'disbursed'
            ])->default('draft')->change()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_phases', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }
};
