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
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->string('group_code', 20)->nullable()->after('group_id');

            $table->string('gfs_code')->nullable()->after('group_code');

            $table->index(['group_code', 'constituency_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_applications', function (Blueprint $table) {
            $table->dropIndex(['group_code', 'constituency_code']);
            $table->dropColumn(['group_code', 'gfs_code']);
        });
    }
};
