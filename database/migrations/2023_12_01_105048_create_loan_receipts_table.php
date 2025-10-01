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
        Schema::create('loan_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_loans_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('receipt_number',20);
            $table->date('transaction_date');
            $table->integer('amount');
            $table->enum('payment', ['Mpesa', 'Bank']);
            $table->string('reference_number', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_receipts');
    }
};
