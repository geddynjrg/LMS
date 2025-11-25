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
        Schema::create('me_reportings', function (Blueprint $table) {
            $table->id();
            $table->string('constituency_code');
            $table->string('financial_year'); // e.g., "2024-2025"
            $table->string('data_required');
            $table->string('unit_of_measure');
            $table->string('q1_achieved')->nullable(); // quarter 1 achievement
            $table->string('q2_achieved')->nullable(); // quarter 2 achievement
            $table->string('q3_achieved')->nullable(); // quarter 3 achievement
            $table->string('q4_achieved')->nullable(); // quarter 4 achievement
            $table->string('annual_achieved')->nullable(); // whole financial year
            $table->text('remarks_comments')->nullable();
            $table->timestamps();

            $table->foreign('constituency_code')->references('constituency_code')->on('constituencies');
            $table->index(['constituency_code', 'financial_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('me_reportings');
    }
};
