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
        Schema::create('group_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_phase_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('level', ['Refinancing', 'New', 'Resubmission']);
            $table->string('status', 50)->nullable();
            $table->string('comments', 450)->nullable();
            $table->date('application_date')->nullable();
            $table->enum('loan_status', ['pending', 'deffered', 'approved', 'financed', 'cancelled']);
            $table->date('disbursement_date')->nullable();
            $table->string('cheque_no', 50)->nullable();
            $table->integer('amount_approved')->nullable();
            $table->integer('amount_applied')->nullable();
            $table->integer('amount_issued')->nullable();
            $table->integer('amount_repaid')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_loans');
    }
};
