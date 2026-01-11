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
        Schema::create('temp_application_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_application_id')->constrained('temp_applications')->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->string('level');
            $table->string('action');
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_application_approvals');
    }
};
