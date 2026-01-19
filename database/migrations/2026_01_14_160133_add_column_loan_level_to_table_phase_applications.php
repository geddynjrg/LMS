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
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->integer('loan_level')->default(1)->after('group_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->dropColumn('loan_level');
        });
    }
};
