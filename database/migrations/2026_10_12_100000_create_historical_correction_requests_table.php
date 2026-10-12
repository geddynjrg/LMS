<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historical_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->default('group_loan');
            $table->string('constituency_code', 16)->index();
            $table->unsignedBigInteger('group_loan_id')->nullable()->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->json('fields');
            $table->text('message')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('group_loan_id')
                ->references('id')
                ->on('group_loans')
                ->nullOnDelete();
            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_correction_requests');
    }
};
