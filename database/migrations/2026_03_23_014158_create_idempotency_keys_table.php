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
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();

            $table->string('key')->unique();

            // 'processing', 'completed', 'failed'
            $table->string('status')->default('processing')->index();

            // Who initiated the action?
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Audit metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Store the incoming request payload (remarks, specific app IDs, etc.)
            $table->json('request_payload')->nullable();

            // Store the result or error message if it fails
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
