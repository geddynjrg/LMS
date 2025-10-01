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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained();
            $table->string('name');
            $table->string('identifier');
            $table->string('code')->index();
            $table->string('gfs_code')->unique()->nullable();
            $table->string('reg_number')->unique();
            $table->date('date_formed')->nullable();
            $table->date('date_registered')->nullable();
            $table->string('bank')->nullable();
            $table->string('account')->nullable();
            $table->enum('category', ['Youth', 'Women', 'PWD']);
            $table->enum('activity', ['Trade', 'Service', 'Manufacturing', 'Agriculture', 'Other']);
            $table->string('activity_desc')->nullable();
            $table->string('location')->nullable();
            $table->integer('box')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
