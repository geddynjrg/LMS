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
        Schema::create('loan_phases', function (Blueprint $table) {
            $table->id();
            $table->string('constituency_code');
            $table->foreign('constituency_code')->references('constituency_code')->on('constituencies')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('phase');
            $table->integer('amount_disbursed')->nullable();
            $table->integer('amount_repaid')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_phases');
    }
};
