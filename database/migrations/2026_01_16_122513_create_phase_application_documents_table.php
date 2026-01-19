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
        Schema::create('phase_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_phase_id')->constrained('loan_phases')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('document_source')->default('local'); // local, s3, etc.
            $table->string('document_type')->index();
            $table->string('original_filename');
            $table->string('filename');
            $table->string('path');
            $table->string('url')->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_application_documents');
    }
};
