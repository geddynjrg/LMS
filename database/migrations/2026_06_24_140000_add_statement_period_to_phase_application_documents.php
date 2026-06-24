<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phase_application_documents', function (Blueprint $table) {
            $table->string('statement_period', 7)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('phase_application_documents', function (Blueprint $table) {
            $table->dropColumn('statement_period');
        });
    }
};
