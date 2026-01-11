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
            $table->dropForeign(['group_id']);

            $table->dropColumn(['group_id', 'group_code', 'gfs_code']);
        });


        Schema::table('phase_applications', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('temp_application_id')->constrained()->onDelete('set null');
            $table->string('group_code', 20)->nullable()->after('group_id');
            $table->string('gfs_code', 255)->nullable()->after('group_code');

            $table->unique(['loan_phase_id', 'group_id'], 'one_group_per_phase_unique');

            $table->index('group_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phase_applications', function (Blueprint $table) {
            $table->dropUnique('one_group_per_phase_unique');

            $table->dropForeign(['group_id']);

            $table->dropColumn(['group_id', 'group_code', 'gfs_code']);
        });

        Schema::table('temp_applications', function (Blueprint $table) {
            $table->bigInteger('group_id')->unsigned()->nullable()->after('link_status');
            $table->string('group_code', 20)->nullable()->after('group_id');
            $table->string('gfs_code', 255)->nullable()->after('group_code');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }
};
