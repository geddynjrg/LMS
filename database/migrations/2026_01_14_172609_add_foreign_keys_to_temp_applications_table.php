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
        Schema::table('temp_applications', function (Blueprint $col) {
            $col->foreign('ward_id')
                ->references('id')
                ->on('wards')
                ->onDelete('set null');

            $col->foreign('constituency_code')
                ->references('constituency_code')
                ->on('constituencies')
                ->onDelete('set null');

            $col->foreign('county_code')
                ->references('county_code')
                ->on('counties')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_applications', function (Blueprint $col) {
            $col->dropForeign(['ward_id']);
            $col->dropForeign(['constituency_code']);
            $col->dropForeign(['county_code']);
        });
    }
};
