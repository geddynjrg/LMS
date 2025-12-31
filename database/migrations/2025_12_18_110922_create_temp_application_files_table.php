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
        Schema::create('temp_application_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_application_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->string('original_filename');
            $table->string('filename');
            $table->string('path');
            $table->string('url')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('size')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_application_files');
    }
};
