<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->date('training_start_date')->nullable()->after('training_completed_at');
            $table->date('training_end_date')->nullable()->after('training_start_date');
            $table->string('training_venue')->nullable()->after('training_end_date');
            $table->text('training_notes')->nullable()->after('training_venue');
        });
    }

    public function down(): void
    {
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->dropColumn([
                'training_start_date',
                'training_end_date',
                'training_venue',
                'training_notes',
            ]);
        });
    }
};
